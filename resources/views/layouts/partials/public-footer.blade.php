@php
    $hasContact = $site->address || $site->phone || $site->email || $site->whatsapp_url;
@endphp

<footer class="public-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="{{ route('home') }}" class="public-brand">
                    @if ($site->logo_url)
                        <img src="{{ $site->logo_url }}" alt="{{ $site->name }}" class="brand-logo">
                    @else
                        <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
                        <span>{{ $site->name }}</span>
                    @endif
                </a>

                @if ($site->footer)
                    <p style="margin-top:1rem">{{ $site->footer }}</p>
                @endif

                @if (count($site->socials) > 0)
                    <div class="social-links">
                        @foreach ($site->socials as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}">
                                <i class="bi {{ $social['icon'] }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h4>Kontak</h4>
                @if ($hasContact)
                    <ul class="footer-links">
                        @if ($site->address)
                            <li><i class="bi bi-geo-alt"></i><span>{{ $site->address }}</span></li>
                        @endif
                        @if ($site->phone)
                            <li><i class="bi bi-telephone"></i><span>{{ $site->phone }}</span></li>
                        @endif
                        @if ($site->email)
                            <li><i class="bi bi-envelope"></i><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>
                        @endif
                        @if ($site->whatsapp_url)
                            <li><i class="bi bi-whatsapp"></i><a href="{{ $site->whatsapp_url }}" target="_blank" rel="noopener noreferrer">{{ $site->whatsapp }}</a></li>
                        @endif
                    </ul>
                @else
                    <p>Informasi kontak belum diatur.</p>
                @endif
            </div>

            <div>
                <h4>Informasi</h4>
                @if ($site->additional_info)
                    <p>{{ $site->additional_info }}</p>
                @else
                    <p>{{ $site->description }}</p>
                @endif
            </div>
        </div>

        <div class="footer-bottom">
            <span>{{ $site->copyright }}</span>
            <span>Zona waktu: {{ config('app.timezone') }}</span>
        </div>
    </div>
</footer>
