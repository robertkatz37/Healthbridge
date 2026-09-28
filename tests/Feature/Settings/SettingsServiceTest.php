<?php

use App\Models\Setting;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->settings = app(SettingsService::class);
});

test('set and get round-trips a plain string value', function () {
    $this->settings->set('test_key', 'hello world', 'general', 'string');

    expect($this->settings->get('test_key'))->toBe('hello world');
});

test('boolean values are correctly normalized regardless of input type', function () {
    // Regression test: set() previously did a bare truthy check on the
    // input, which treats the STRING 'false' as truthy in PHP, silently
    // inverting the stored value. See DATABASE_DECISIONS.md.
    $this->settings->set('bool_from_string_false', 'false', 'general', 'bool');
    $this->settings->set('bool_from_string_true', 'true', 'general', 'bool');
    $this->settings->set('bool_from_real_false', false, 'general', 'bool');
    $this->settings->set('bool_from_real_true', true, 'general', 'bool');

    expect($this->settings->get('bool_from_string_false'))->toBeFalse();
    expect($this->settings->get('bool_from_string_true'))->toBeTrue();
    expect($this->settings->get('bool_from_real_false'))->toBeFalse();
    expect($this->settings->get('bool_from_real_true'))->toBeTrue();
});

test('int type casts the stored value to an integer', function () {
    $this->settings->set('test_int', '42', 'general', 'int');

    expect($this->settings->get('test_int'))->toBe(42);
    expect($this->settings->get('test_int'))->toBeInt();
});

test('json type round-trips an array', function () {
    $this->settings->set('test_json', ['a', 'b', 'c'], 'general', 'json');

    expect($this->settings->get('test_json'))->toBe(['a', 'b', 'c']);
});

test('sensitive values are encrypted at rest in the database', function () {
    $this->settings->set('secret_key', 'topsecret123', 'mail', 'string', encrypted: true);

    $raw = DB::table('settings')->where('key', 'secret_key')->first();

    expect($raw->value)->not->toContain('topsecret123');
    expect($this->settings->get('secret_key'))->toBe('topsecret123');
});

test('getGroup returns all settings in a group with values cast', function () {
    $this->settings->set('group_test_a', 'value_a', 'test_group', 'string');
    $this->settings->set('group_test_b', 'true', 'test_group', 'bool');

    $group = $this->settings->getGroup('test_group');

    expect($group)->toBe(['group_test_a' => 'value_a', 'group_test_b' => true]);
});

test('getRaw returns the undecoded string without type casting', function () {
    $this->settings->set('raw_test', '007', 'general', 'string');

    expect($this->settings->getRaw('raw_test'))->toBe('007');
});

test('missing key returns the provided default', function () {
    expect($this->settings->get('nonexistent_key_xyz', 'fallback'))->toBe('fallback');
});

test('writing a setting clears its cache so subsequent reads see the new value', function () {
    $this->settings->set('cache_test', 'first', 'general', 'string');
    expect($this->settings->get('cache_test'))->toBe('first');

    $this->settings->set('cache_test', 'second', 'general', 'string');
    expect($this->settings->get('cache_test'))->toBe('second');
});

test('forget deletes the setting and clears its cache', function () {
    $this->settings->set('forget_test', 'value', 'general', 'string');
    $this->settings->forget('forget_test');

    expect(Setting::where('key', 'forget_test')->exists())->toBeFalse();
    expect($this->settings->get('forget_test', 'default'))->toBe('default');
});
