<?php

namespace App\Http\Controllers\Yonetim;

use App\Models\Urun;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UrunController extends Controller
{
    public function index()
    {
        if(request()->filled('aranan'))
        {
            request()->flash();
            $aranan = request('aranan');
            $list = Urun::where('urun_adi','like',"%$aranan%")
                ->orWhere('aciklama','like',"%$aranan%")
                ->orderByDesc('id')
                ->paginate(8)
                ->appends('aranan',$aranan);
        }
        else{
            $list = Urun::orderByDesc('id')->paginate(8);
        }
        return view('yonetim.urun.index', compact('list'));
    }

    public function form($id= 0)
    {
        $entry = new Kullanici;
        if($id>0){
            $entry = Kullanici::find($id);
        }
        return view('yonetim.kullanici.form', compact('entry'));
    }

    public function kaydet($id= 0)
    {
        $this->validate(request(), [
            'adsoyad' => 'required',
            'email' => 'required|email',
        ]);

        $data = request()->only('adsoyad','email');
        if(request()->filled('sifre')) {
            $data['sifre'] = Hash::make(request('sifre'));
        }
        $data['aktif_mi'] = request()->has('aktif_mi') && request('aktif_mi')==1 ? 1 : 0;
        $data['yonetici_mi'] = request()->has('yonetici_mi') && request('yonetici_mi')==1 ? 1 : 0;

        if($id>0) {
            $entry = Kullanici::where('id', $id)->firstOrFail();
            $entry->update($data);
        }
        else {
            $entry = Kullanici::create($data);
        }
        KullaniciDetay::updateOrCreate(
            ['kullanici_id'=> $entry->id],
            [
                'adres' => request('adres'),
                'telefon'=> request('telefon')
            ]
        );
        return redirect()
            ->route('yonetim.kullanici.duzenle', $entry->id)
            ->with('mesaj', ($id>0 ? 'Güncellendi' : 'Kaydedildi'))
            ->with('mesaj_tur', 'success');
    }

    public function sil($id){
        Kullanici::destroy($id);

        return redirect()
            ->route('yonetim.kullanici')
            ->with('mesaj','Kayıt Silindi')
            ->with('mesaj_tur', 'success');
    }
}
