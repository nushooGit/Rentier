<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Renter;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()
            ->where('email', 'test@example.com')
            ->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
        }

        $team = $user->currentTeam ?? Team::query()->firstOrCreate(
            ['slug' => 'rentier-demo'],
            ['name' => 'Rentier Demo', 'is_personal' => false],
        );

        if (! $user->belongsToTeam($team)) {
            $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
        }

        $user->switchTeam($team);

        $centralApartment = Property::query()->updateOrCreate([
            'team_id' => $team->id,
            'name' => 'Apartament Unirii',
        ], [
            'type' => 'apartment',
            'country' => 'Romania',
            'city' => 'București',
            'county_or_sector' => 'Sector 3',
            'address_line' => 'Bulevardul Unirii 24',
            'rooms' => 2,
            'usable_area_sqm' => 58,
            'floor' => 4,
            'total_floors' => 8,
            'status' => 'occupied',
            'monthly_rent_amount' => 2500,
            'currency' => 'RON',
            'rent_due_day' => 5,
            'deposit_amount' => 2500,
            'notes' => 'Aproape de metrou.',
        ]);

        $drumulTaberei = Property::query()->updateOrCreate([
            'team_id' => $team->id,
            'name' => 'Garsonieră Drumul Taberei',
        ], [
            'type' => 'studio',
            'country' => 'Romania',
            'city' => 'București',
            'county_or_sector' => 'Sector 6',
            'address_line' => 'Strada Brașov 12',
            'rooms' => 1,
            'usable_area_sqm' => 34,
            'floor' => 2,
            'total_floors' => 10,
            'status' => 'occupied',
            'monthly_rent_amount' => 1800,
            'currency' => 'RON',
            'rent_due_day' => 10,
            'deposit_amount' => 1800,
        ]);

        Property::query()->updateOrCreate([
            'team_id' => $team->id,
            'name' => 'Apartament Titan',
        ], [
            'type' => 'apartment',
            'country' => 'Romania',
            'city' => 'București',
            'county_or_sector' => 'Sector 3',
            'address_line' => 'Strada Liviu Rebreanu 8',
            'rooms' => 3,
            'usable_area_sqm' => 72,
            'floor' => 6,
            'total_floors' => 10,
            'status' => 'available',
            'monthly_rent_amount' => 3000,
            'currency' => 'RON',
            'rent_due_day' => 5,
            'deposit_amount' => 3000,
        ]);

        $ion = Renter::query()->updateOrCreate([
            'team_id' => $team->id,
            'email' => 'ion.popescu@example.test',
        ], [
            'name' => 'Ion Popescu',
            'phone' => '0712 345 678',
            'notes' => 'Preferă transfer bancar.',
        ]);

        $maria = Renter::query()->updateOrCreate([
            'team_id' => $team->id,
            'email' => 'maria.ionescu@example.test',
        ], [
            'name' => 'Maria Ionescu',
            'phone' => '0723 456 789',
            'notes' => 'Contact după ora 17:00.',
        ]);

        $leaseIon = Lease::query()->updateOrCreate([
            'team_id' => $team->id,
            'property_id' => $centralApartment->id,
            'renter_id' => $ion->id,
            'start_date' => '2026-01-01',
        ], [
            'end_date' => '2026-12-31',
            'monthly_rent_amount' => 2500,
            'currency' => 'RON',
            'rent_due_day' => 5,
            'deposit_amount' => 2500,
            'status' => 'active',
            'notes' => 'Contract semnat la începutul anului.',
        ]);

        $leaseMaria = Lease::query()->updateOrCreate([
            'team_id' => $team->id,
            'property_id' => $drumulTaberei->id,
            'renter_id' => $maria->id,
            'start_date' => '2026-03-01',
        ], [
            'end_date' => null,
            'monthly_rent_amount' => 1800,
            'currency' => 'RON',
            'rent_due_day' => 10,
            'deposit_amount' => 1800,
            'status' => 'active',
        ]);

        foreach ([
            [$leaseIon, 6, 2026, 2500, '2026-06-05', 'bank_transfer', 'paid'],
            [$leaseIon, 5, 2026, 2500, '2026-05-05', 'bank_transfer', 'paid'],
            [$leaseMaria, 6, 2026, 1800, '2026-06-10', 'cash', 'paid'],
            [$leaseMaria, 5, 2026, 900, '2026-05-10', 'cash', 'partial'],
        ] as [$lease, $month, $year, $amount, $date, $method, $status]) {
            RentPayment::query()->updateOrCreate([
                'team_id' => $team->id,
                'lease_id' => $lease->id,
                'period_month' => $month,
                'period_year' => $year,
            ], [
                'property_id' => $lease->property_id,
                'renter_id' => $lease->renter_id,
                'amount' => $amount,
                'currency' => 'RON',
                'payment_date' => $date,
                'method' => $method,
                'status' => $status,
                'notes' => null,
            ]);
        }

        Expense::query()->updateOrCreate([
            'team_id' => $team->id,
            'property_id' => $centralApartment->id,
            'title' => 'Reparație instalație baie',
            'expense_date' => '2026-06-12',
        ], [
            'lease_id' => $leaseIon->id,
            'category' => 'repairs',
            'amount' => 650,
            'currency' => 'RON',
            'paid_by' => 'landlord',
            'status' => 'paid',
            'notes' => 'Schimbat baterie și racorduri.',
        ]);

        Expense::query()->updateOrCreate([
            'team_id' => $team->id,
            'property_id' => $drumulTaberei->id,
            'title' => 'Întreținere bloc',
            'expense_date' => '2026-06-18',
        ], [
            'lease_id' => $leaseMaria->id,
            'category' => 'utilities',
            'amount' => 320,
            'currency' => 'RON',
            'paid_by' => 'renter',
            'status' => 'reimbursable',
            'notes' => 'De recuperat la următoarea plată.',
        ]);

        Expense::query()->updateOrCreate([
            'team_id' => $team->id,
            'property_id' => $centralApartment->id,
            'title' => 'Asigurare locuință',
            'expense_date' => '2026-05-20',
        ], [
            'lease_id' => null,
            'category' => 'insurance',
            'amount' => 480,
            'currency' => 'RON',
            'paid_by' => 'landlord',
            'status' => 'paid',
            'notes' => null,
        ]);
    }
}
