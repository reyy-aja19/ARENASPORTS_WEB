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

        $rewards = Reward::where('status', 'Aktif')
            ->orderBy('points_required')
            ->get()
            ->map(fn (Reward $reward) => $this->rewardPayload($reward))
            ->values();

        $redemptions = Redemption::with('reward')
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

            if ($lockedUser->points < $reward->points_required) {
                abort(422, 'Poin tidak mencukupi.');
            }

            $lockedUser->decrement('points', $reward->points_required);

            $redemption = Redemption::create([
                'user_id' => $lockedUser->id,
                'reward_id' => $reward->id,
                'redeemed_at' => now(),
                'qr_code' => 'AR-'.$lockedUser->id.'-'.$reward->id.'-'.Str::upper(Str::random(6)),
                'status' => 'Berhasil',
            ]);

            $lockedUser->notify(new ArenaUserNotification(
                'Penukaran Reward Berhasil',
                'Reward '.$reward->title.' berhasil ditukar. Poin telah dipotong dari saldo kamu.',
                route('user.rewards', [], false)
            ));

            return $redemption;
        });

        return $this->ok([
            'points' => (int) $user->fresh()->points,
            'redemption' => [
                'id' => (int) $redemption->id,
                'qr_code' => $redemption->qr_code,
                'status' => $redemption->status,
                'redeemed_at' => $redemption->redeemed_at?->toIso8601String(),
                'reward' => $this->rewardPayload($reward),
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
        ];
    }
}
