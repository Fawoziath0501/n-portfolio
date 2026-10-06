@php($en = $lang === 'en')
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $en ? 'Leave a testimonial' : 'Laisser un témoignage' }} · {{ $owner }}</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<style>
  :root { --ink: #0B1530; --mu: #55607A; --ac: #2448C8; --ln: #E1E6F0; --bg: #F3F5F9; --bad: #C4352B; }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--bg); color: var(--ink); font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; line-height: 1.55; }
  .wrap { max-width: 680px; margin: 0 auto; padding: 40px 20px 64px; }
  .brand { font-family: "Courier New", monospace; color: var(--ac); font-size: 14px; text-decoration: none; }
  .brand b { color: var(--ink); }
  .card { margin-top: 20px; background: #FFFFFF; border: 1px solid var(--ln); border-radius: 12px; padding: 32px; }
  h1 { margin: 0 0 8px; font-size: 28px; line-height: 1.2; }
  .lead { margin: 0 0 24px; color: var(--mu); }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  label.f { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; font-size: 14px; font-weight: 600; }
  label.f small { font-weight: 400; color: var(--mu); }
  input[type=text], input[type=email], select, textarea { width: 100%; font: inherit; font-weight: 400; color: var(--ink); background: #F8F9FC; border: 1px solid var(--ln); border-radius: 8px; padding: 12px 14px; }
  textarea { min-height: 150px; resize: vertical; }
  input:focus, select:focus, textarea:focus { outline: 2px solid var(--ac); outline-offset: 1px; background: #FFFFFF; }
  .bad { border-color: var(--bad) !important; }
  .err { color: var(--bad); font-size: 13px; font-weight: 400; }
  .consent { display: flex; gap: 10px; align-items: flex-start; font-size: 14px; margin: 4px 0 18px; }
  .consent input { margin-top: 3px; width: 18px; height: 18px; flex: none; }
  .hp { position: absolute; left: -10000px; width: 1px; height: 1px; opacity: 0; }
  button { font: inherit; font-weight: 700; border: 0; border-radius: 8px; background: var(--ac); color: #FFFFFF; padding: 14px 22px; cursor: pointer; }
  button:hover { background: #1B379E; }
  .note { margin-top: 16px; font-size: 13px; color: var(--mu); }
  .ok { text-align: center; }
  .ok a { display: inline-block; margin-top: 8px; color: var(--ac); font-weight: 600; }
  @media (max-width: 600px) { .grid { grid-template-columns: 1fr; gap: 0; } .card { padding: 24px 18px; } h1 { font-size: 24px; } }
</style>
@if($captcha && empty($thanks))
  @php($src = ['turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/api.js', 'recaptcha' => 'https://www.google.com/recaptcha/api.js?hl='.$lang, 'hcaptcha' => 'https://js.hcaptcha.com/1/api.js?hl='.$lang][$captcha['provider']] ?? null)
  @if($src)<script src="{{ $src }}" async defer></script>@endif
@endif
</head>
<body>
<div class="wrap">
  <a class="brand" href="{{ $site }}">[ FS ] <b>{{ $owner }}</b></a>
  <main class="card">
    @if(!empty($thanks))
      <div class="ok">
        <h1>{{ $en ? 'Thank you very much!' : 'Merci beaucoup !' }}</h1>
        <p class="lead">{{ $en ? 'Your testimonial has been received. I will read it and publish it on the website shortly.' : 'Votre témoignage a bien été reçu. Je le relis et le publie très prochainement sur le site.' }}</p>
        <a href="{{ $site }}">{{ $en ? 'Visit the website →' : 'Découvrir le site →' }}</a>
      </div>
    @else
      <h1>{{ $en ? 'Leave a testimonial' : 'Laisser un témoignage' }}</h1>
      <p class="lead">{{ $en ? 'We worked together? A few words about our collaboration would mean a lot and help future clients and recruiters. Thank you for your time!' : 'Nous avons travaillé ensemble ? Quelques mots sur notre collaboration comptent beaucoup et aident les futurs clients et recruteurs. Merci pour votre temps !' }}</p>

      <form method="post" action="{{ $action }}" novalidate id="tform">
        @csrf
        <div class="grid">
          <label class="f">{{ $en ? 'Full name' : 'Nom et prénom' }} *
            <input type="text" name="name" value="{{ old('name') }}" maxlength="120" required autocomplete="name" class="{{ $errors->has('name') ? 'bad' : '' }}">
            @error('name')<span class="err">{{ $message }}</span>@enderror
          </label>
          <label class="f">E-mail * <small>{{ $en ? 'not published, used only to verify' : 'non publié, sert uniquement à vérifier' }}</small>
            <input type="email" name="email" value="{{ old('email') }}" maxlength="180" required autocomplete="email" class="{{ $errors->has('email') ? 'bad' : '' }}">
            @error('email')<span class="err">{{ $message }}</span>@enderror
          </label>
          <label class="f">{{ $en ? 'Position' : 'Fonction' }} <small>{{ $en ? 'e.g. Project manager' : 'ex. Chef de projet' }}</small>
            <input type="text" name="role" value="{{ old('role') }}" maxlength="120" autocomplete="organization-title">
          </label>
          <label class="f">{{ $en ? 'Company or organisation' : 'Entreprise ou organisation' }}
            <input type="text" name="company" value="{{ old('company') }}" maxlength="120" autocomplete="organization">
          </label>
        </div>
        @if(count($projects))
        <label class="f">{{ $en ? 'Project concerned' : 'Projet concerné' }} <small>{{ $en ? 'optional' : 'facultatif' }}</small>
          <select name="project_id">
            <option value="">{{ $en ? '— Choose —' : '— Choisir —' }}</option>
            @foreach($projects as $p)<option value="{{ $p['id'] }}" @selected(old('project_id') == $p['id'])>{{ $p['title'] }}</option>@endforeach
          </select>
        </label>
        @endif
        <label class="f">{{ $en ? 'Your testimonial' : 'Votre témoignage' }} *
          <textarea name="quote" maxlength="1500" required class="{{ $errors->has('quote') ? 'bad' : '' }}" placeholder="{{ $en ? 'How was our collaboration? What did the project bring you?' : 'Comment s’est passée notre collaboration ? Qu’est-ce que le projet vous a apporté ?' }}">{{ old('quote') }}</textarea>
          @error('quote')<span class="err">{{ $message }}</span>@enderror
        </label>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <label class="consent">
          <input type="checkbox" name="consent" value="1" @checked(old('consent'))>
          <span>{{ $en ? 'I agree that this testimonial may be published on this website, together with my name, position and company.' : 'J’accepte que ce témoignage soit publié sur ce site, avec mon nom, ma fonction et mon entreprise.' }}</span>
        </label>
        @error('consent')<p class="err">{{ $message }}</p>@enderror
        @if($captcha)
          <div class="{{ ['turnstile' => 'cf-turnstile', 'recaptcha' => 'g-recaptcha', 'hcaptcha' => 'h-captcha'][$captcha['provider']] ?? '' }}" data-sitekey="{{ $captcha['siteKey'] }}" style="margin-bottom:16px"></div>
          <input type="hidden" name="captcha" value="">
          @error('captcha')<p class="err">{{ $message }}</p>@enderror
        @endif
        <button type="submit">{{ $en ? 'Send my testimonial' : 'Envoyer mon témoignage' }}</button>
        <p class="note">{{ $en ? 'Your testimonial will be read before being published.' : 'Votre témoignage sera relu avant d’être publié.' }}</p>
      </form>
      @if($captcha)
      <script>
        document.getElementById('tform').addEventListener('submit', function () {
          var t = document.querySelector('[name="cf-turnstile-response"],[name="g-recaptcha-response"],[name="h-captcha-response"]');
          this.querySelector('[name="captcha"]').value = t ? t.value : '';
        });
      </script>
      @endif
    @endif
  </main>
</div>
</body>
</html>
