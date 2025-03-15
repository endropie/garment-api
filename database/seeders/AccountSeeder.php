<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;

class AccountSeeder extends Seeder
{
    protected \Faker\Generator $fake;
    public function run(): void
    {
        $this->fake = fake('ID_id');
        $this->fakeAccount(5);
    }

    protected function fakeAccount($limit = 1)
    {
        for ($i=0; $i < $limit; $i++)
        {
            $name = $this->fake->unique()->lastName();
            $request = new Request([
                "name" => $name,
                "email" => strtolower("$name.owner@appmember.com"),
                "phone" => $this->fake->unique()->phoneNumber(),
                "password" => "password",
                "password_confirmation" => "password",
            ]);

            $request->merge([
                "tenant" => [
                    "name" => $name . $this->fake->randomElement([' Store', ' Inc', ' Fashion', ' Co', ' Boutique'])
                ]
            ]);

            $response = app(\App\Http\ApiControllers\AccountAuthController::class)->register($request);

            $this->accountTenantAccess($response->original['tenant']);
        }
    }

    protected function accountTenantAccess(array $tenant)
    {

        foreach (['CASHIER', 'LEADER', 'ADMIN'] as $item) {
            $tenant = Tenant::find($tenant['id']);

            $account = \App\Models\Account::create([
                'name' => strtok($tenant->name, ' ') . ucfirst(" $item"),
                'email' => strtolower(strtok($tenant->name, ' ') . ".$item" . '@appmember.com'),
                'phone' => $this->fake->unique()->phoneNumber(),
                'password' => app('hash')->make('password'),
            ]);

            $account->access()->attach($tenant, [
                'ability' => json_encode([$item])
            ]);
        }
    }

    static function fakeAccountLogin($id = null)
    {
        Sanctum::actingAs(
            \App\Models\Account::find($id ?? 1)
        );
    }
}
