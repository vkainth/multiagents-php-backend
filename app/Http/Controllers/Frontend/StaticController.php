<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Helpers\AgentContext;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;


class StaticController extends Controller
{
    /**
     * Build the common data array expected by agent theme views,
     * mirroring AgentController::view().
     */
    private function agentThemeView(string $template): \Illuminate\Contracts\View\View
    {
        $agent = AgentContext::current();
        $territories = $agent->territories()->get()->groupBy('city');

        $common = [
            'agent'            => $agent,
            'territories'      => $territories,
            'agentTheme'       => $agent->theme_slug ?? 'classic-dark',
            'agentThemeColor'  => $agent->theme_color ?? '#c9a96e',
            'testimonialCount' => $agent->testimonials()->count(),
        ];

        return view("themes.{$agent->theme_slug}.{$template}", $common);
    }


    public function privacyPolicy()
    {
        return view('frontend.privacy_policy');
    }
    public function termsConditions()
    {
        return view('frontend.terms_conditions');
    }

    public function rebgvTermsConditions()
    {
        return view('frontend.registrant_terms_conditions');
    }

    public function sell()
    {
        return view('frontend.sell');
    }

    public function test_sell()
    {
        return view('frontend.test_sell');
    }

    public function test_buy()
    {
        return view('frontend.test_buy');
    }
    public function test_wt_all()
    {
        return view('frontend.test_wt_all');
    }
    /**
     * tsbPages new-design [team,sell,buy] pages (Mark-designed)
     * @param  String  $tsbPage which-page to show
     * @return [type]           [description]
     */
    public function tsbPages($tsbPage=null, $idSection=null )
    {
        return view('frontend.tsb_pages', compact('tsbPage','idSection') );
    }

    public function buy()
    {
        return view('frontend.buy');
    }

    public function aboutUs()
    {
        return view('frontend.about_us');
    }


    public function newsblog()
    {
        if(\Request::is('blog*')){
            return view('frontend.newsblog',['newsmode'=>'blogpostnews']);
        }elseif(\Request::is('news-blog*')){
            return view('frontend.newsblog',['newsmode'=>'news-blog']);
        }elseif(\Request::is('news-victoria*')){
            return view('frontend.newsblog',['newsmode'=>'news-victoria']);
        }elseif(\Request::is('news-mandarin*')){
            return view('frontend.newsblog',['newsmode'=>'news-mandarin']);
        }elseif(\Request::is('news') || \Request::is('news/*')){
            return view('frontend.newsblog',['newsmode'=>'news']);
        }
    }

    public function sellersGuide()
    {
        if (AgentContext::hasAgent()) {
            return $this->agentThemeView('sellers-guide');
        }
        return view('frontend.sellers_guide');
    }

    public function buyersGuide()
    {
        if (AgentContext::hasAgent()) {
            return $this->agentThemeView('buyers-guide');
        }
        return view('frontend.buyers_guide');
    }

    public function ssmuhGuide()
    {
        return view('frontend.ssmuh_guide');
    }

    public function buyingDuplexGuide()
    {
        return view('frontend.buying_duplex_guide');
    }

    public function buyingFourplexGuide()
    {
        return view('frontend.buying_fourplex_guide');
    }

    public function showReviews(){
        return view('frontend.google_reviews');
    }
}
