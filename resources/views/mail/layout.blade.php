{{-- Mise en page commune des e-mails envoyés aux visiteurs (tableaux et styles en ligne : lisible dans Gmail, Outlook et sur mobile). --}}
@php($en = $me['lang'] === 'en')
<!DOCTYPE html>
<html lang="{{ $me['lang'] }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>@yield('title')</title>
</head>
<body style="margin:0;padding:0;background:#F3F5F9;font-family:Arial,Helvetica,sans-serif;color:#0B1530;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F5F9;">
  <tr><td align="center" style="padding:32px 16px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
      {{-- En-tête --}}
      <tr><td style="padding:0 4px 16px;font-family:'Courier New',monospace;font-size:14px;color:#2448C8;">
        [ FS ] <span style="color:#0B1530;font-weight:bold;">{{ $me['shortName'] }}</span>
      </td></tr>
      {{-- Carte --}}
      <tr><td style="background:#FFFFFF;border:1px solid #E1E6F0;border-radius:12px;overflow:hidden;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          <tr><td style="height:4px;background:#2448C8;font-size:0;line-height:0;">&nbsp;</td></tr>
          <tr><td style="padding:32px 32px 8px;">
            @yield('content')
          </td></tr>
          {{-- Signature --}}
          <tr><td style="padding:8px 32px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E1E6F0;">
              <tr><td style="padding-top:20px;font-size:15px;line-height:1.6;">
                <div style="color:#55607A;">{{ $en ? 'Kind regards,' : 'Bien cordialement,' }}</div>
                <div style="font-weight:bold;font-size:16px;margin-top:6px;">{{ $me['name'] }}</div>
                <div style="color:#2448C8;">{{ $me['title'] }}@if($me['location']) · {{ $me['location'] }}@endif</div>
                <div style="margin-top:8px;font-size:14px;color:#55607A;">
                  @if($me['phone']){{ $en ? 'Phone:' : 'Tél. :' }} <a href="tel:{{ preg_replace('/\s+/', '', $me['phone']) }}" style="color:#0B1530;text-decoration:none;">{{ $me['phone'] }}</a><br>@endif
                  @if($me['whatsapp'])WhatsApp{{ $en ? ':' : ' :' }} <a href="{{ $me['whatsapp']['url'] }}" style="color:#0B1530;text-decoration:none;">{{ $me['whatsapp']['label'] }}</a><br>@endif
                  @if($me['email'])E-mail{{ $en ? ':' : ' :' }} <a href="mailto:{{ $me['email'] }}" style="color:#0B1530;text-decoration:none;">{{ $me['email'] }}</a><br>@endif
                  <a href="{{ $me['site'] }}" style="color:#2448C8;">{{ $me['host'] }}</a>@if($me['linkedin']) · <a href="{{ $me['linkedin'] }}" style="color:#2448C8;">LinkedIn</a>@endif
                </div>
              </td></tr>
            </table>
          </td></tr>
        </table>
      </td></tr>
      {{-- Pied --}}
      <tr><td style="padding:16px 8px 0;font-size:12px;line-height:1.5;color:#7A859C;text-align:center;">
        @yield('footer')
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
