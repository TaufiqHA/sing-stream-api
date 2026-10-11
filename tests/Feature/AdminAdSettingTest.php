<?php

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guest is redirected to login when accessing admin ads page', function () {
    $response = $this->get('/admin/ads');

    $response->assertRedirect('/login');
});

test('regular user is forbidden from accessing admin ads page', function () {
    $user = User::factory()->create([
        'role' => UserRole::User,
    ]);

    $response = $this->actingAs($user)->get('/admin/ads');

    $response->assertStatus(403);
});

test('admin can view ads page even if no settings record exists yet', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/ads');

    $response->assertStatus(200);
    $response->assertSee('Konfigurasi Iklan');
    $response->assertSee('Iklan Utama');
    $response->assertSee('Iklan Bawah');
    $response->assertSee('Simpan Iklan');
    $response->assertSee('type="file"', false);
    $response->assertSee('enctype="multipart/form-data"', false);
    $response->assertSee('Ad Unit ID AdMob');
});

test('admin can view ads page with existing banner preview', function () {
    $admin = User::factory()->admin()->create();
    Setting::factory()->create([
        'applicationads1' => 'https://example.com/banner-top.jpg',
        'applicationads2' => 'banners/popup.jpg',
        'applicationadsactive' => 'Y',
        'applicationadsbottom' => 'banners/bottom.jpg',
        'applicationadsbottomactive' => 'N',
    ]);

    $response = $this->actingAs($admin)->get('/admin/ads');

    $response->assertStatus(200);
    $response->assertSee('https://example.com/banner-top.jpg');
    $response->assertSee('popup.jpg');
    $response->assertSee('bottom.jpg');
});

test('admin can save ads and upload banner files when record does not exist initially', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    expect(Setting::count())->toBe(0);

    $ad1 = UploadedFile::fake()->image('banner1.jpg', 600, 200);
    $ad2 = UploadedFile::fake()->image('banner2.png', 400, 400);
    $adBottom = UploadedFile::fake()->image('banner-bottom.webp', 800, 100);

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationads1' => $ad1,
        'applicationads2' => $ad2,
        'applicationadsactive' => 'Y',
        'applicationadsbottom' => $adBottom,
        'applicationadsbottomactive' => 'Y',
    ]);

    $response->assertRedirect('/admin/ads');
    $response->assertSessionHas('success', 'Konfigurasi iklan berhasil disimpan.');

    expect(Setting::count())->toBe(1);
    $setting = Setting::first();
    expect($setting->applicationadsactive)->toBe('Y')
        ->and($setting->applicationadsbottomactive)->toBe('Y')
        ->and($setting->applicationads1)->not->toBeNull()
        ->and($setting->applicationads2)->not->toBeNull()
        ->and($setting->applicationadsbottom)->not->toBeNull();

    Storage::disk('public')->assertExists($setting->applicationads1);
    Storage::disk('public')->assertExists($setting->applicationads2);
    Storage::disk('public')->assertExists($setting->applicationadsbottom);
});

test('admin can upload new banner file and delete old file', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $oldFile = UploadedFile::fake()->image('old-banner.jpg');
    $oldPath = $oldFile->store('banners', 'public');

    $setting = Setting::factory()->create([
        'applicationads1' => $oldPath,
    ]);

    Storage::disk('public')->assertExists($oldPath);

    $newFile = UploadedFile::fake()->image('new-banner.jpg');

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationads1' => $newFile,
        'applicationadsactive' => 'Y',
        'applicationadsbottomactive' => 'Y',
    ]);

    $response->assertRedirect('/admin/ads');
    $setting->refresh();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($setting->applicationads1);
});

test('admin can retain existing banner if no new file is uploaded', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $bannerFile = UploadedFile::fake()->image('keep-banner.jpg');
    $bannerPath = $bannerFile->store('banners', 'public');

    $setting = Setting::factory()->create([
        'applicationads1' => $bannerPath,
    ]);

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationadsactive' => 'Y',
        'applicationadsbottomactive' => 'Y',
    ]);

    $response->assertRedirect('/admin/ads');
    $setting->refresh();

    expect($setting->applicationads1)->toBe($bannerPath);

    Storage::disk('public')->assertExists($bannerPath);
});

test('admin can remove banner by selecting delete checkbox', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $bannerFile = UploadedFile::fake()->image('banner-to-delete.jpg');
    $bannerPath = $bannerFile->store('banners', 'public');

    $setting = Setting::factory()->create([
        'applicationads1' => $bannerPath,
    ]);

    Storage::disk('public')->assertExists($bannerPath);

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationadsactive' => 'Y',
        'applicationadsbottomactive' => 'Y',
        'delete_applicationads1' => '1',
    ]);

    $response->assertRedirect('/admin/ads');
    $setting->refresh();

    expect($setting->applicationads1)->toBeNull();
    Storage::disk('public')->assertMissing($bannerPath);
});

test('validation fails when uploaded file is not an image or exceeds 2MB', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $nonImage = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
    $oversizedImage = UploadedFile::fake()->image('huge.jpg')->size(3000); // 3MB

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationads1' => $nonImage,
        'applicationads2' => $oversizedImage,
        'applicationadsactive' => 'Y',
        'applicationadsbottomactive' => 'Y',
    ]);

    $response->assertSessionHasErrors([
        'applicationads1',
        'applicationads2',
    ]);
});

test('admin can save admob ad type with ad unit id without uploading image', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationads1type' => 'admob_native',
        'applicationads1unit' => 'ca-app-pub-1234567890123456/1111111111',
        'applicationads2type' => 'admob_banner',
        'applicationads2unit' => 'ca-app-pub-1234567890123456/3333333333',
        'applicationadsactive' => 'Y',
        'applicationadsbottomtype' => 'admob_interstitial',
        'applicationadsbottomunit' => 'ca-app-pub-1234567890123456/2222222222',
        'applicationadsbottomactive' => 'Y',
    ]);

    $response->assertRedirect('/admin/ads');
    $response->assertSessionHasNoErrors();

    $setting = Setting::first();
    expect($setting->applicationads1type)->toBe('admob_native')
        ->and($setting->applicationads1unit)->toBe('ca-app-pub-1234567890123456/1111111111')
        ->and($setting->applicationads2type)->toBe('admob_banner')
        ->and($setting->applicationads2unit)->toBe('ca-app-pub-1234567890123456/3333333333')
        ->and($setting->applicationadsbottomtype)->toBe('admob_interstitial')
        ->and($setting->applicationadsbottomunit)->toBe('ca-app-pub-1234567890123456/2222222222');
});

test('admin save requires ad unit id when ad type is admob', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationads1type' => 'admob_banner',
        'applicationads1unit' => '',
        'applicationadsactive' => 'Y',
        'applicationadsbottomactive' => 'Y',
    ]);

    $response->assertSessionHasErrors(['applicationads1unit']);
});

test('admin save rejects unknown ad type', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put('/admin/ads', [
        'applicationads1type' => 'unknown_type',
        'applicationadsactive' => 'Y',
        'applicationadsbottomactive' => 'Y',
    ]);

    $response->assertSessionHasErrors(['applicationads1type']);
});
