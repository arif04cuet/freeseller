<?php

namespace App\Livewire;

use App\Models\Faq as ModelsFaq;
use Livewire\Component;

class Faq extends Component
{
    public function render()
    {
        $faqs = ModelsFaq::query()->orderBy('sort_order')->get();
        return view('livewire.faq', ['faqs' => $faqs]);
    }
}
