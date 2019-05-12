<?php

namespace App\Http\Controllers\Yonetim;

use App\Models\Kategori;
use App\Models\Siparis;
use App\Models\Urun;
use App\Models\UrunDetay;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Validator;

class SiparisController extends Controller
{
    public function index()
    {
        if(request()->filled('aranan'))
        {
            request()->flash();
            $aranan = request('aranan');
            $list = Siparis::where('adsoyad','like',"%$aranan%")
                ->orWhere('id',$aranan)
                ->orderByDesc('id')
                ->paginate(8)
                ->appends('aranan',$aranan);
        }
        else{
            $list = Siparis::orderByDesc('id')->paginate(8);
        }
        return view('yonetim.siparis.index', compact('list'));
    }

    public function form($id= 0)
    {
        if($id>0){
            $entry = Siparis::with('sepet.sepet_urunler.urun')->find($id);
        }

        return view('yonetim.siparis.form', compact('entry'));
    }

    public function kaydet($id= 0)
    {
        $data = request()->only('urun_adi','slug','aciklama','fiyati');
        if(!request()->filled('slug')) {
            $data['slug'] = str_slug(request('urun_adi'));
            request()->merge(['slug' => $data['slug']]);
        }
        $this->validate(request(), [
            'urun_adi' => 'required',
            'fiyati'       => 'required',
            'slug'         => (request('original_slug')!=request('slug') ? 'unique:urun,slug': '')
        ]);
        $data_detay = request()->only('goster_slider','goster_gunun_firsati',
            'goster_one_cikan','goster_cok_satan','goster_indirimli');

        $kategoriler = request('kategoriler');

        if($id>0) {
            $entry = Urun::where('id', $id)->firstOrFail();
            $entry->update($data);
            $entry->detay()->update($data_detay);
            $entry->kategoriler()->sync($kategoriler);
        }
        else {
            $entry = Urun::create($data);
            $entry->detay()->create($data_detay);
            $entry->kategoriler()->attach($kategoriler);
        }

        if(request()->hasFile('urun_resmi'))
        {
            $this->validate(request(),[
               'urun_resmi' => 'image|mimes:jpg,png,jpeg,gif|max:2048'
            ]);

            $urun_resmi = request()->file('urun_resmi');
            $urun_resmi = request()->urun_resmi;

            $dosyaadi = $entry->id . "-". time(). ".". $urun_resmi->extension();

            if($urun_resmi->isValid())
            {
                $urun_resmi->move('uploads/urunler',$dosyaadi);

                UrunDetay::updateOrCreate(
                    ['urun_id'    => $entry->id],
                    ['urun_resmi' => $dosyaadi]
                );
            }
        }

        return redirect()
            ->route('yonetim.urun.duzenle', $entry->id)
            ->with('mesaj', ($id>0 ? 'Güncellendi' : 'Kaydedildi'))
            ->with('mesaj_tur', 'success');
    }

    public function sil($id){
        $urun = Urun::find($id);
        $urun->kategoriler()->detach();
        $urun->delete();

        return redirect()
            ->route('yonetim.urun')
            ->with('mesaj','Kayıt Silindi')
            ->with('mesaj_tur', 'success');
    }
}