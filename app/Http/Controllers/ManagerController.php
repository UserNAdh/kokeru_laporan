<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use  App\Models\Laporan;
use  App\Models\Ruangan;
use  App\Models\CS;
use  App\Models\Managers;
use Carbon\Carbon;
use Hash;
use DB;
use Excel;
use PDF;
use Dompdf\Dompdf;
use App\Exports\LaporanExport;

class ManagerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->reportDate = new Carbon();
        $this->now = new Carbon();
        $this->status = 'semua';
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function index(string $page)
    {
        switch ($page) {
            case 'dashboard':
                $laporan = Laporan::where('tanggal',date('y-m-d'))
                                    ->orderBy('id',)
                                    ->Paginate(12);
                return view("managers.{$page}")->with('laporan',$laporan);
                break;
            case 'dataCS':
                $cs = CS::all();
                return view("managers.{$page}")->with('cs',$cs);
                break;
            case 'ruangan':
                $ruangan = Ruangan::all();
                return view("managers.{$page}",['ruangan'=>$ruangan], compact('ruangan'));
                break;
            case 'laporan':
                $laporan = Laporan::all();
                return view('managers/laporan', [
                    'reportDate' => new Carbon(),
                    'now' => new Carbon(),
                    'status' => 'semua',
                    'laporan' => Laporan::where([['tanggal',date('y-m-d')]])->orderBy('id')->get()
                ], compact('laporan'));
                break;
            case 'profile':
                $manager = Managers::find(AUTH::id());
                return view("managers.{$page}")->with('manager',$manager);
                break;
            default:
                return abort(404);
                break;
        }
       
    }
    public function getlaporan($id_laporan){
        $laporan = Laporan::find($id_laporan);
        return view('managers.bukti')->with('laporan',$laporan);
    }
    public function getRuangan($id){
        $ruangan = Ruangan::find($id);
        $cs = DB::table('cs')->select('id','name')->get();
        return view('managers.editRuang',['cs'=>$cs,'ruangan'=>$ruangan], compact('ruangan'))->with('ruangan',$ruangan);
    }
    public function tambahRuangan(){
        $cs = DB::table('cs')->select('id','name')->get();
        return view('managers.addRuang')->with('cs',$cs);
    }
    public function pilihTanggal(Request $request){
            if($request->status == "semua"){
                $laporan = Laporan::where([['tanggal',$request->tanggal]])->orderBy('id')->get();
            }else{
                $laporan = Laporan::where([['tanggal',$request->tanggal],['status', $request->status == "sudah"?1:0 ]])->orderBy('id')->get();
            }
            
            return view('managers/laporan', [
                'reportDate' => Carbon::parse($request->tanggal),
                'now' => new Carbon(),
                'status' => $request->status,
                'laporan' => $laporan
            ], compact('laporan'));
        }

        public function exportExcel($tanggal,$status){
            return Excel::download(new LaporanExport($tanggal,$status), (new Carbon()).'.xlsx');
        }
        public function exportPdf($tanggal,$status){
            $tanggal = \Carbon\Carbon::parse($tanggal)->Format('Y/m/d');
            if($this->status == "semua"){
                $laporan =  Laporan::where([['tanggal',$tanggal]])->get();
            }else{
                $laporan = Laporan::where([['tanggal',$tanggal],['status',$status == "sudah"?1:0]])->get();
            }
            
            $pdf = PDF::loadView('managers.exportView', ['laporan'=>$laporan,'tanggal'=>$tanggal]);
	        return $pdf->download((new Carbon()).'.pdf');
            
        }
        public function update(Request $request, $id){
            $this->validate($request,[
                'name' =>'required|string|max:255',
                'email' => ' required|unique:CS'.($id ? ",id,$id" : '').'|email|max:255|',
                'password' => 'required|min:8'
            ]);
            $manager = Managers::find($id);
            $manager->name = $request->name;
            $manager->email = $request->email;
            $manager->password = Hash::make($request->password);
            $manager->save();
            return back()->with('success',"Profile berhasil diubah");
        }
       

}