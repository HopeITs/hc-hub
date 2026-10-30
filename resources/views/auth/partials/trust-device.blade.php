{{-- "Do not ask again on this device" (App\Actions\Fortify\TrustedDevices::RememberField). --}}
<label for="remember_device" class="flex min-h-11 cursor-pointer items-center justify-center gap-2 text-body text-ink-2" data-test="trust-device">
    <input id="remember_device" type="checkbox" name="remember_device" value="1" checked
           class="size-4 rounded-inset border border-primary bg-field text-primary focus:ring-primary" />
    {{ __('security.trust_this_device', ['days' => \App\Actions\Fortify\TrustedDevices::DaysTrusted]) }}
</label>
