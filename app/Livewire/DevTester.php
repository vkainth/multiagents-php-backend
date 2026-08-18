<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Session;
use Livewire\Attributes\Layout;

class DevTester extends Component
{
    #[Reactive]
    public $theValue;

    #[Session] 
    public $sessVal;

    public function changeShowStaged()
    {
        Debugbar::info(' changeShowStaged-called >> Switch-sessn: '.((session()->get('bcch_showStgdChngesv9xLvM')??'not-set')?'true':'false' ) );
    }

    #[Layout('frontend.layouts.default_mobilefirst')] 
    public function render()
    {
        return view('livewire.dev-tester');
    }
}
