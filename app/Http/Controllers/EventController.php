<?php
namespace App\Http\Controllers;
use App\Models\Event; use Illuminate\Http\Request; use Illuminate\Http\RedirectResponse; use Illuminate\View\View;
class EventController extends Controller {
 private function data(Request $r): array { return $r->validate(['title'=>['required','string','max:255'],'jenis'=>['required','string','max:100'],'tanggal'=>['required','date','after_or_equal:today'],'start_time'=>['nullable','date_format:H:i'],'end_time'=>['nullable','date_format:H:i'],'lokasi'=>['required','string','max:255'],'kuota'=>['required','integer','min:1','max:10000'],'deskripsi'=>['nullable','string','max:5000'],'status'=>['required','in:Publikasi,Draft,Dibatalkan']]); }
 public function index(): View { $events=Event::with('creator')->latest()->get(); return view('admin.events.index',compact('events')); }
 public function create(): View { $event=new Event(['jenis'=>'Futsal','kuota'=>20,'status'=>'Draft']); return view('admin.events.form',compact('event')); }
 public function store(Request $r): RedirectResponse { $data=$this->data($r); $data['created_by']=$r->session()->get('admin_user_id'); Event::create($data); return redirect()->route('admin.control-center', ['tab' => 'events'])->with('success','Event berhasil dibuat.'); }
 public function edit(Event $event): View { return view('admin.events.form',compact('event')); }
 public function update(Request $r, Event $event): RedirectResponse { $event->update($this->data($r)); return redirect()->route('admin.control-center', ['tab' => 'events'])->with('success','Event berhasil diperbarui.'); }
 public function destroy(Event $event): RedirectResponse { $event->delete(); return back()->with('success','Event berhasil dihapus.'); }
}
