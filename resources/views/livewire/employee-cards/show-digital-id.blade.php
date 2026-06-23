@php
    $hasPhone = filled($employee->work_phone) && filled($phoneHref);
    $hasTelegram = filled($employee->telegram_work) && filled($telegramHref);
    $hasLinkedin = filled($employee->linkedin) && filled($linkedinHref);
    $hasContactLink = $hasPhone || $hasTelegram || $hasLinkedin;
@endphp

<div class="digital-id-page">
    <style>
        .digital-id-page {
            --id-primary: var(--color-primary, #c2151c);
            --id-black: var(--color-black, #414042);
            --id-icon: #414042;
            min-height: 100vh;
            padding: clamp(1.25rem, 5vw, 4rem) 1rem;
            color: var(--id-black);
            background: #ffffff;
            font-family: 'SomarSans', 'Lato', sans-serif;
        }

        .digital-id-wrap {
            width: min(94vw, 32rem);
            margin: 0 auto;
            text-align: center;
        }

        .digital-id-logo {
            width: clamp(8.5rem, 38vw, 15rem);
            margin: 0 auto clamp(2rem, 8vw, 4.25rem);
        }

        .digital-id-card {
            position: relative;
            display: flex;
            flex-direction: column;
            height: auto;
            min-height: 0;
            overflow: hidden;
            border-radius: clamp(2rem, 8vw, 3.2rem);
            background: #ffffff;
            box-shadow: 0 1rem 2rem rgb(65 64 66 / 22%);
        }

        .digital-id-photo-panel {
            position: relative;
            flex: none;
            height: clamp(18rem, 72vw, 30rem);
            overflow: hidden;
            background: #ffffff;
        }

        .digital-id-photo-bar {
            position: absolute;
            inset-inline: 0;
            bottom: 0;
            height: clamp(3.15rem, 11vw, 4.6rem);
            background: var(--id-primary);
        }

        .digital-id-avatar {
            position: absolute;
            bottom: 0;
            left: 50%;
            z-index: 1;
            display: block;
            width: min(92%, 27rem);
            max-height: 94%;
            transform: translateX(-50%);
            object-fit: contain;
            object-position: bottom center;
        }

        .digital-id-info {
            display: flex;
            flex: none;
            flex-direction: column;
            align-items: center;
            min-height: 0;
            padding: clamp(1.15rem, 4.2vw, 2rem) clamp(1.25rem, 5vw, 2.5rem) clamp(1.5rem, 5vw, 2.4rem);
        }

        .digital-id-name {
            max-width: 100%;
            color: var(--id-primary);
            font-size: clamp(2.65rem, 10.5vw, 4.75rem);
            font-weight: 900;
            line-height: 0.88;
            letter-spacing: 0;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }

        .digital-id-position {
            max-width: 100%;
            margin-top: clamp(0.7rem, 2.6vw, 1rem);
            color: var(--id-black);
            font-size: clamp(1.28rem, 4.6vw, 2.1rem);
            font-weight: 700;
            line-height: 1.02;
            overflow-wrap: anywhere;
        }

        .digital-id-center {
            max-width: 100%;
            margin-top: 0.22rem;
            color: var(--id-black);
            font-size: clamp(0.98rem, 3.5vw, 1.48rem);
            font-weight: 700;
            line-height: 1.05;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }

        .digital-id-city {
            max-width: 100%;
            margin-top: clamp(0.9rem, 3.4vw, 1.5rem);
            color: var(--id-primary);
            font-size: clamp(1rem, 3.5vw, 1.48rem);
            font-weight: 700;
            line-height: 1.05;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }

        .digital-id-divider {
            width: 70%;
            height: 0.12rem;
            margin-top: clamp(1rem, 3.8vw, 1.65rem);
            background: #bcbec0;
        }

        .digital-id-icons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: clamp(0.55rem, 2vw, 0.9rem);
            margin-top: clamp(1rem, 3.8vw, 1.7rem);
        }

        .digital-id-icon-link {
            display: inline-flex;
            width: clamp(2.1rem, 7.8vw, 3rem);
            height: clamp(2.1rem, 7.8vw, 3rem);
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            color: #ffffff;
            background: var(--id-icon);
            transition: transform 160ms ease, background-color 160ms ease;
        }

        .digital-id-icon-link:hover,
        .digital-id-icon-link:focus-visible {
            background: var(--id-primary);
            transform: translateY(-1px);
        }

        .digital-id-icon {
            width: 62%;
            height: 62%;
            fill: currentColor;
        }

        .digital-id-emergency {
            margin-top: clamp(1.75rem, 6vw, 3.1rem);
            color: var(--id-black);
            font-size: clamp(1rem, 3.6vw, 1.55rem);
            font-weight: 500;
            line-height: 1.2;
        }

        .digital-id-emergency a,
        .digital-id-emergency strong {
            color: var(--id-black);
            font-size: clamp(1.12rem, 4vw, 1.75rem);
            font-weight: 700;
            text-decoration: none;
        }
    </style>

    <main class="digital-id-wrap" aria-label="Employee digital ID">
        <img class="digital-id-logo" src="{{ asset('assets/img/001_HC.svg') }}" alt="Christian Hope Center">

        <section class="digital-id-card">
            <div class="digital-id-photo-panel">
                <div class="digital-id-photo-bar" aria-hidden="true"></div>
                <img class="digital-id-avatar" src="{{ $avatarSrc }}" alt="{{ $employee->name }}">
            </div>

            <div class="digital-id-info">
                <h1 class="digital-id-name">
                    <span>{{ $employee->first_name }}</span><br>
                    <span>{{ $employee->last_name }}</span>
                </h1>

                <div class="digital-id-position">{{ $position?->title }}</div>
                <div class="digital-id-center">{{ $center?->name }}</div>
                <div class="digital-id-city">{{ $city?->name }}</div>

                <div class="digital-id-divider" aria-hidden="true"></div>

                @if ($hasContactLink)
                    <div class="digital-id-icons" aria-label="Employee contact links">
                        @if ($hasPhone)
                            <a class="digital-id-icon-link" href="{{ $phoneHref }}" aria-label="Call {{ $employee->name }}">
                                <svg class="digital-id-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.61 21 3 13.39 3 4c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.24.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                </svg>
                            </a>
                        @endif

                        @if ($hasTelegram)
                            <a class="digital-id-icon-link" href="{{ $telegramHref }}" target="_blank" rel="noopener noreferrer" aria-label="Open Telegram for {{ $employee->name }}">
                                <svg class="digital-id-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M21.94 4.16a1.27 1.27 0 0 0-1.42-.2L3.3 11.07c-.48.2-.8.62-.8 1.08 0 .55.42.96 1.08 1.08l4.35.78 1.66 5.22c.18.55.56.86 1.03.86.35 0 .64-.17.9-.48l2.4-2.89 4.54 3.36c.27.2.56.31.85.31.67 0 1.13-.47 1.28-1.28l2.35-13.57c.1-.58-.1-1.09-.5-1.38zM18.72 7.4l-7.46 6.68-.3 3.09-1.1-3.48 8.86-6.29z"/>
                                </svg>
                            </a>
                        @endif

                        @if ($hasLinkedin)
                            <a class="digital-id-icon-link" href="{{ $linkedinHref }}" target="_blank" rel="noopener noreferrer" aria-label="Open LinkedIn for {{ $employee->name }}">
                                <svg class="digital-id-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M19 3A2 2 0 0 1 21 5v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zM8.34 18.34V9.75H5.67v8.59h2.67zM7 8.58c.85 0 1.54-.69 1.54-1.54S7.85 5.5 7 5.5s-1.54.69-1.54 1.54S6.15 8.58 7 8.58zm11.34 9.76v-4.71c0-2.32-1.24-3.39-2.9-3.39-1.33 0-1.93.73-2.26 1.25V9.75h-2.66v8.59h2.66v-4.79c0-1.26.24-2.48 1.8-2.48 1.53 0 1.55 1.43 1.55 2.56v4.71h2.81z"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <p class="digital-id-emergency">
            EMERGENCY CONTACT:
            @if (filled($emergencyPhone) && filled($emergencyPhoneHref))
                <a href="{{ $emergencyPhoneHref }}">{{ $emergencyPhone }}</a>
            @else
                <strong>Not available</strong>
            @endif
        </p>
    </main>
</div>
