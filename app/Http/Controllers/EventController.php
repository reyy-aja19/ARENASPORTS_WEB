<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventController extends Controller
{
    private function data(Request $r): array
    {
        return $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['required', 'string', 'max:255'],
            'kuota' => ['required', 'integer', 'min:1', 'max:10000'],
            'registration_fee' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'prize' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'registration_deadline' => ['nullable', 'date'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:Publikasi,Draft,Dibatalkan'],
        ]);
    }

    private function isSuperadmin(Request $r): bool
    {
        return $r->session()->get('admin_role') === 'superadmin';
    }

    public function index(Request $r): View
    {
        $query = Event::with('creator');
        if (!$this->isSuperadmin($r)) {
            $query->where('created_by', $r->session()->get('admin_user_id'));
        }
        $events = $query->withCount('registrations')->latest()->get();

        return view('admin.events.index', compact('events'));
    }

    public function create(Request $r): View
    {
        $userId = $r->session()->get('admin_user_id');
        $venues = $this->isSuperadmin($r)
            ? Venue::orderBy('name')->get()
            : Venue::where('owner_admin_id', $userId)->orderBy('name')->get();
        $defaultLokasi = $venues->first()?->name ?: '';

        $event = new Event([
            'jenis' => 'Futsal',
            'kuota' => 20,
            'registration_fee' => 0,
            'status' => 'Publikasi',
            'lokasi' => $defaultLokasi,
        ]);

        return view('admin.events.form', compact('event', 'venues'));
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $this->data($r);
        $data['created_by'] = $r->session()->get('admin_user_id');
        $event = Event::create($data);

        // Notifikasi realtime ke semua user jika status event Publikasi
        if ($event->status === 'Publikasi') {
            User::where('role', 'user')->where('status', 'Aktif')->get()->each(function (User $user) use ($event) {
                $user->notify(new ArenaUserNotification(
                    'Event Resmi Baru: ' . $event->title,
                    'Event olahraga ' . $event->title . ' (' . $event->jenis . ') telah dibuka di ' . $event->lokasi . '. Kuota ' . $event->kuota . ' peserta.',
                    route('user.openmatches', [], false)
                ));
            });
        }

        if ($r->has('_control_tab')) {
            return redirect()->route('admin.control-center', ['tab' => 'events'])->with('success', 'Event berhasil dipublikasikan.');
        }

        return redirect()->route('admin.events.index')->with('success', 'Event berhasil dipublikasikan.');
    }

    public function edit(Request $r, Event $event): View
    {
        abort_unless($this->isSuperadmin($r) || (int) $event->created_by === (int) $r->session()->get('admin_user_id'), 403);

        $userId = $r->session()->get('admin_user_id');
        $venues = $this->isSuperadmin($r)
            ? Venue::orderBy('name')->get()
            : Venue::where('owner_admin_id', $userId)->orderBy('name')->get();

        return view('admin.events.form', compact('event', 'venues'));
    }

    public function update(Request $r, Event $event): RedirectResponse
    {
        abort_unless($this->isSuperadmin($r) || (int) $event->created_by === (int) $r->session()->get('admin_user_id'), 403);

        $wasNotPublished = $event->status !== 'Publikasi';
        $event->update($this->data($r));

        if ($wasNotPublished && $event->status === 'Publikasi') {
            User::where('role', 'user')->where('status', 'Aktif')->get()->each(function (User $user) use ($event) {
                $user->notify(new ArenaUserNotification(
                    'Event Resmi Baru: ' . $event->title,
                    'Event olahraga ' . $event->title . ' (' . $event->jenis . ') telah dibuka di ' . $event->lokasi . '. Kuota ' . $event->kuota . ' peserta.',
                    route('user.openmatches', [], false)
                ));
            });
        }

        if ($r->has('_control_tab')) {
            return redirect()->route('admin.control-center', ['tab' => 'events'])->with('success', 'Event berhasil diperbarui.');
        }

        return redirect()->route('admin.events.index')->with('success', 'Event berhasil diperbarui.');
    }

    public function registrations(Request $r, Event $event): View
    {
        abort_unless($this->canManage($r, $event), 403);

        $event->load(['creator', 'venue']);
        $registrations = $event->registrations()
            ->with('user')
            ->latest()
            ->get();

        return view('admin.events.registrations', compact('event', 'registrations'));
    }

    public function updateRegistrationStatus(Request $r, Event $event, \App\Models\EventRegistration $registration): RedirectResponse
    {
        abort_unless($this->canManage($r, $event), 403);
        abort_unless((int) $registration->event_id === (int) $event->id, 404);

        $data = $r->validate([
            'status' => ['required', 'in:Baru,Dihubungi,Terkonfirmasi,Ditolak'],
        ]);

        $registration->update(['status' => $data['status']]);

        if ($registration->user) {
            $registration->user->notify(new ArenaUserNotification(
                'Status Pendaftaran Event',
                'Pendaftaran tim '.$registration->team_name.' untuk event '
                    .$event->title.' kini berstatus '.$data['status'].'.',
                route('user.openmatches', [], false)
            ));
        }

        return back()->with('success', 'Status pendaftar berhasil diperbarui.');
    }

    private function canManage(Request $r, Event $event): bool
    {
        return $this->isSuperadmin($r)
            || (int) $event->created_by === (int) $r->session()->get('admin_user_id');
    }

    public function destroy(Request $r, Event $event): RedirectResponse
    {
        abort_unless($this->isSuperadmin($r) || (int) $event->created_by === (int) $r->session()->get('admin_user_id'), 403);

        $event->delete();
        return back()->with('success', 'Event berhasil dihapus.');
    }
}
