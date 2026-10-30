/**
 * Passkeys (laravel/passkeys) for the security page and password confirmation.
 * Loaded only by the passkey components, not by every page.
 */
import { Passkeys } from '@laravel/passkeys';

window.Passkeys = Passkeys;
window.dispatchEvent(new CustomEvent('passkeys:ready'));
