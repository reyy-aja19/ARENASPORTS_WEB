<?php

namespace App\Http\Controllers\Api;

use App\Models\Redemption;
use App\Models\Reward;
use App\Models\User;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);

        $rewards = Reward::with(['venue', 'owner'])
            ->where('status', 'Aktif')
            ->orderBy('points_required')
            ->get()
            ->map(fn (Reward $reward) => $this->rewardPayload($reward))
            ->values();

        $redemptions = Redemption::with(['reward.venue', 'reward.owner', 'venue'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (Redemption $redemption) => [
                'id' => (int) $redemption->id,
                'reward_title' => $redemption->reward?->title,
                'reward_badge' => $redemption->reward?->badge,
                'qr_code' => $redemption->qr_code,
                'status' => $redemption->status,
                'redeemed_at' => $redemption->redeemed_at?->toIso8601String(),
                'verified_at' => $redemption->verified_at?->toIso8601String(),
                'venue' => $redemption->venue?->name ?? $redemption->reward?->venue?->name,
                'venue_id' => $redemption->venue_id ?? $redemption->reward?->venue_id,
                'provider' => $redemption->reward?->provider_label,
            ])->values();

        return $this->ok([
            'points' => (int) $user->points,
            'rewards' => $rewards,
            'redemptions' => $redemptions,
        ]);
    }

    public function redeem(Request $request, Reward $reward)
    {
        $user = $this->currentUser($request);

        if ($reward->status !== 'Aktif') {
            return $this->fail('Reward sedang tidak tersedia.', 422);
        }

        if ($user->points < $reward->points_required) {
            return $this->fail('Poin kamu belum mencukupi.', 422);
        }

        $redemption = DB::transaction(function () use ($user, $reward) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedReward = Reward::with(['venue', 'owner'])->whereKey($reward->id)->lockForUpdate()->firstOrFail();

            if ($lockedReward->status !== 'Aktif') {
                abort(422, 'Reward sedang tidak tersedia.');
            }

            if ($lockedUser->points < $lockedReward->points_required) {
                abort(422, 'Poin tidak mencukupi.');
            }

            $lockedUser->decrement('points', $lockedReward->points_required);

            $redemption = Redemption::create([
                'user_id' => $lockedUser->id,
                'reward_id' => $lockedReward->id,
                'owner_admin_id' => $lockedReward->owner_admin_id,
                'venue_id' => $lockedReward->venue_id,
                'redeemed_at' => now(),
                'qr_code' => 'AR-'.$lockedUser->id.'-'.$lockedReward->id.'-'.Str::upper(Str::random(6)),
                'status' => 'Berhasil',
            ]);

            $venueLabel = $lockedReward->venue?->name ?: 'ArenaSports';
            $lockedUser->notify(new ArenaUserNotification(
                'Penukaran Reward Berhasil',
                'Reward '.$lockedReward->title.' berhasil ditukar. Berlaku di: '.$venueLabel.'. Poin telah dipotong dari saldo kamu.',
                route('user.rewards', [], false)
            ));

            return $redemption->load(['reward.venue', 'reward.owner', 'venue']);
        });

        return $this->ok([
            'points' => (int) $user->fresh()->points,
            'redemption' => [
                'id' => (int) $redemption->id,
                'qr_code' => $redemption->qr_code,
                'status' => $redemption->status,
                'redeemed_at' => $redemption->redeemed_at?->toIso8601String(),
                'verified_at' => $redemption->verified_at?->toIso8601String(),
                'venue' => $redemption->venue?->name ?? $redemption->reward?->venue?->name,
                'venue_id' => $redemption->venue_id ?? $redemption->reward?->venue_id,
                'provider' => $redemption->reward?->provider_label,
                'reward' => $this->rewardPayload($redemption->reward ?? $reward),
            ],
        ], 'Reward berhasil ditukar. Poin telah dipotong dari saldo kamu.');
    }

    private function rewardPayload(Reward $reward): array
    {
        return [
            'id' => (int) $reward->id,
            'title' => $reward->title,
            'points_required' => (int) $reward->points_required,
            'badge' => $reward->badge,
            'description' => $reward->description,
            'image_url' => $this->absoluteImage($reward->image),
            'status' => $reward->status,
            'venue_id' => $reward->venue_id ? (int) $reward->venue_id : null,
            'venue_name' => $reward->venue?->name,
            'provider' => $reward->provider_label,
            'owner_admin_id' => $reward->owner_admin_id ? (int) $reward->owner_admin_id : null,
        ];
    }
}
