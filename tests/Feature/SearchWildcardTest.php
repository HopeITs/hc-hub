<?php

use App\Models\Donor;
use App\Models\User;
use App\Support\Like;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

test('Like escapes wildcard characters and the escape character', function () {
    expect(Like::escape('50%_off\\'))->toBe('50\\%\\_off\\\\')
        ->and(Like::contains('  UNI  '))->toBe('%UNI%');
});

test('Like only searches for the first 100 characters typed', function () {
    expect(Like::contains(str_repeat('ب', 150)))->toBe('%'.str_repeat('ب', 100).'%')
        ->and(Like::term('  '.str_repeat('a', 99).' b c  '))->toBe(str_repeat('a', 99).' ');
});

test('a bare wildcard typed in a search box is matched literally', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->role('Financial')->create();
    Donor::factory()->create(['name' => 'UNICEF', 'country' => null, 'contact_person' => null]);
    Donor::factory()->create(['name' => '100% Aid', 'country' => null, 'contact_person' => null]);

    Livewire::actingAs($user)
        ->test('pages::donors.index')
        ->set('search', '%')
        ->assertSee('100% Aid')
        ->assertDontSee('UNICEF')
        ->set('search', 'UNI_EF')
        ->assertDontSee('UNICEF')
        ->set('search', 'UNI')
        ->assertSee('UNICEF');
});

test('register search boxes fold Arabic letters and read the other keyboard layout', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->role('Financial')->create();
    Donor::factory()->create(['name' => 'UNICEF', 'country' => null, 'contact_person' => null]);
    Donor::factory()->create(['name' => 'مؤسسة الإغاثة', 'country' => null, 'contact_person' => null]);

    Livewire::actingAs($user)
        ->test('pages::donors.index')
        ->set('search', 'ىهؤثب')
        ->assertSee('UNICEF')
        ->assertDontSee('مؤسسة الإغاثة')
        ->set('search', 'موسسه الاغاثه')
        ->assertSee('مؤسسة الإغاثة')
        ->assertDontSee('UNICEF');
});

test('register search boxes match each word, with «ال» optional, and leave short searches as typed', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->role('Financial')->create();
    Donor::factory()->create(['name' => 'مؤسسة الإغاثة', 'country' => null, 'contact_person' => null]);
    Donor::factory()->create(['name' => 'Hd Partners', 'country' => null, 'contact_person' => null]);

    Livewire::actingAs($user)
        ->test('pages::donors.index')
        ->set('search', 'موسسه اغاثه')
        ->assertSee('مؤسسة الإغاثة')
        ->set('search', 'في')
        ->assertDontSee('Hd Partners');
});

test('register search boxes find each word in any field', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->role('Financial')->create();
    Donor::factory()->create(['name' => 'UNICEF', 'country' => 'Syria', 'contact_person' => null]);

    Livewire::actingAs($user)
        ->test('pages::donors.index')
        ->set('search', 'unicef syria')
        ->assertSee('UNICEF')
        ->set('search', 'unicef jordan')
        ->assertDontSee('UNICEF');
});
