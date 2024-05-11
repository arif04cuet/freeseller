<?php

namespace App\Livewire;

use App\Enum\AddressType;
use App\Models\Address;
use App\Models\Customer;
use DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CustomerSearch extends Component
{

    public $mobile = null;
    public $open = false;

    #[Locked]
    public $selectedCustomerId;

    public $newCustomer = [];

    public function create(): void
    {
        if ($id = $this->selectedCustomerId)
            $cusomer = Customer::find($id);
        else
            $cusomer = Customer::firstOrNew(['mobile' => $this->newCustomer['mobile']]);

        $cusomer->fill($this->newCustomer)->save();

        $cusomer->resellers()->syncWithoutDetaching([auth()->user()->id]);

        $this->selectedCustomer($cusomer->id);

        $this->dispatch('close-modal', id: 'add-customer');
    }

    public function newCustomerModal()
    {
        $this->open = true;
        $this->newCustomer = [
            'name' => '',
            'mobile' => $this->mobile,
            'address' => '',
            'district_id' => null,
            'upazila_id' => null,
        ];

        if ($id = $this->selectedCustomerId) {
            $customer = Customer::with(['district', 'upazila'])->find($id);
            $this->newCustomer = [
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'address' => $customer->address,
                'district_id' => "$customer->district_id",
                'upazila_id' => "$customer->upazila_id",
            ];
        }

        $this->dispatch('open-modal', id: 'add-customer');
    }

    public function selectedCustomer($id)
    {
        $this->selectedCustomerId = $id;
        $this->mobile = null;

        $this->dispatch('customerSelected', id: $id);
    }

    #[Computed()]
    public function fraudMessages()
    {
        return $this->customer->loadMissing('fraudMarkedByResellers')->fraudMarkedByResellers;
    }

    #[Computed()]
    public function districts()
    {
        return Address::query()
            ->select(['id', 'name'])
            ->where('type', AddressType::District->value)
            ->orderBy('name')
            ->get();
    }
    #[Computed()]
    public function upazilas()
    {
        if ($district = $this->newCustomer['district_id'] ?? false)
            return Address::query()
                ->select(['id', 'name'])
                ->where('type', AddressType::Upazila->value)
                ->where('parent_id', $district)
                ->get();

        return [];
    }

    public function fraudList()
    {
        $this->open = true;

        $this->dispatch('open-modal', id: 'fraud-list');
    }

    #[Computed()]
    public function customer()
    {
        if ($id = $this->selectedCustomerId) {
            return Customer::with(['district', 'upazila'])->find($id);
        }

        return null;
    }

    public function removeCustomer()
    {
        $this->mobile = $this->selectedCustomerId = null;

        $this->dispatch('customerSelected', id: null);
    }

    public function render()
    {
        $mobile = $this->mobile;

        $customers = $mobile ? Customer::query()
            ->mine()
            ->select(['id', 'name', 'mobile'])
            ->where('mobile', 'like', "{$mobile}%")
            ->take(5)
            ->get() : collect([]);

        return view('livewire.customer-search', [

            'customers' => $customers
        ]);
    }
}
