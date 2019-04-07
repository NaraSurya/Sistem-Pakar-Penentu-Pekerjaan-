<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Topic;
use App\pekerjaan;
use App\karakteristik;
use Session;


class RecomendationController extends Controller
{
    public function topic(){
        $topic = Topic::all();
        return view('recomendation.topic' , ["topics"=> $topic]);
    }

    public function index(Topic $topic){
        session(['recomendation_list'=>$topic->pekerjaans->sortBy('label')]);
        return $this->pertanyaan();
        //return $this->pertanyaan($topic->pekerjaans->sortBy('label') , $topic);
    }

    public function yesAnswer(Request $request , Topic $topic){
        $yesLabel = session('yes');
        $yesLabel = $yesLabel.$request->karakteristik_code;
        session(['yes'=>$yesLabel]);
        $pekerjaans = session('recomendation_list');
        $pekerjaans = $pekerjaans->filter(function ($pekerjaan) use ($yesLabel){
            return stristr($pekerjaan->label, $yesLabel) ? true : false;
        });
        session(['recomendation_list'=>$pekerjaans->sortBy('label')]);
        return $this->pertanyaan();
        // $pekerjaans = pekerjaan::where('label','like',$yesLabel.'%')->get();
        // return $this->pertanyaan($pekerjaans->sortBy('label') , $topic);
    }

    public function noAnswer(Request $request , Topic $topic){
        $noLabel = $request->karakteristik_code;
        $pekerjaans = session('recomendation_list');
        $pekerjaans = $pekerjaans->filter(function ($pekerjaan) use ($noLabel){
            return stristr($pekerjaan->label, $noLabel) ? false : true;
        });
        session(['recomendation_list'=>$pekerjaans->sortBy('label')]);
        return $this->pertanyaan();
        // $noLabel = session('no');
        // $noLabel = $noLabel.$request->karakteristik_code; 
        // session(['no'=>$noLabel]);
        // $yesLabel = session('yes');
        // $pekerjaans = pekerjaan::where('label','like',$yesLabel.'%')->get();
        // $pekerjaans = $pekerjaans->filter(function ($pekerjaan) use ($noLabel){
        //     return stristr($pekerjaan->label, $noLabel) ? false : true;
        // });
        // return $this->pertanyaan($pekerjaans->sortBy('label') , $topic);
    }

    public function done(){
        session()->flush();
        return redirect('/');
    }

    private function pertanyaan(){
        $codeQuestion = $this->getQuestion();
        if(is_null($codeQuestion)){
            return session('pekerjaan');
        }
        $pertanyaan = karakteristik::where('code',$codeQuestion)->first();
        return view('recomendation.pertanyaan',['pertanyaan'=>$pertanyaan]);

    }


    


    // fungsi pendukung
    private function getNextCode($pekerjaan){
        $label = str_split($pekerjaan->label);
        $status = false;
        $code = null;
        for($startIndex = strlen(session('yes')); $startIndex < strlen($pekerjaan->label) ; $startIndex++){
            if($status && ctype_alpha($label[$startIndex])){
                return $code;
            }
            if(ctype_alpha($label[$startIndex])){
                $status = true;
            }
            $code = $code.$label[$startIndex]; 
        }
        return $code;
        
    }


    private function getQuestion(){
        $codeQuestion = null;
        $pekerjaans = session('recomendation_list');
        foreach($pekerjaans as $pekerjaan){
            if($pekerjaan->label == session('yes')){
                session()->push('pekerjaan',$pekerjaan->id);
                continue;
            }else{
                $codeQuestion = $this->getNextCode($pekerjaan);
                return $codeQuestion;
            }
        }
        return $codeQuestion;
    }
}
