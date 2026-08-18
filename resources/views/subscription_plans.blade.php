@extends('frontend.layouts.default_mobile')
@section('title')
Upgrade Your Access — BC Condos & Homes
@endsection
@section('content')
@include('frontend.includes.header')
@php
$user = Auth::user();
$subscriptionAgent = Helper::format_team_agent_for_display(
    DB::table('bccondosandhomes.team_members')->where('id', 66)->first(),
    'Vancouver'
);
$subscriptionAgentName = $subscriptionAgent['name'];
$subscriptionAgentFirstName = $subscriptionAgent['first'];
$subscriptionAgentInitials = $subscriptionAgent['initials'];
$subscriptionAgentImage = $subscriptionAgent['profile_image'];
$subscriptionAgentTitle = $subscriptionAgent['title'];
$subscriptionAgentAgency = $subscriptionAgent['agency'];
$subscriptionAgentPhone = $subscriptionAgent['phone'];
$subscriptionAgentTel = $subscriptionAgent['tel'];
$subscriptionAgentSms = $subscriptionAgent['sms'];
$subscriptionAgentEmail = $subscriptionAgent['email'];
@endphp

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<script async src="https://js.stripe.com/v3/pricing-table.js"></script>

<style>
*, *::before, *::after { box-sizing: border-box; }

:root {
  --dark:  #231f20;
  --gold:  #e4b123;
  --blue:  #22aae2;
  --green: #1a7a3c;
  --remax: #e31837;
  --bg:    #ffffff;
  --card:  #ffffff;
  --muted: #888888;
  --border:#e5e3df;
  --font-display: 'Playfair Display', Georgia, serif;
  --font-body:    'DM Sans', system-ui, -apple-system, sans-serif;
}

html { scroll-behavior: smooth; }

.sp-wrap {
  font-family: var(--font-body);
  background: var(--bg);
  color: var(--dark);
  padding-bottom: 60px;
  padding-top: 86px;
}

.sp-page {
  max-width: 880px;
  margin: 0 auto;
  padding: 0 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ── CARDS ── */
.sp-card {
  background: var(--card);
  border-radius: 12px;
  overflow: hidden;
}
.sp-card-dark {
  background: var(--dark);
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}
.sp-card-dark::before {
  content: '';
  position: absolute;
  top: -60px; right: -60px;
  width: 240px; height: 240px;
  border-radius: 50%;
  border: 1px solid rgba(228,177,35,0.1);
  pointer-events: none;
}
.sp-card-dark::after {
  content: '';
  position: absolute;
  bottom: -40px; left: -40px;
  width: 160px; height: 160px;
  border-radius: 50%;
  border: 1px solid rgba(34,170,226,0.08);
  pointer-events: none;
}

/* ── 1. HERO ── */
.sp-hero {
  padding: 36px 40px;
  position: relative;
  z-index: 1;
}
.sp-hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold);
  border: 0.5px solid rgba(228,177,35,0.35);
  padding: 4px 10px;
  border-radius: 4px;
  margin-bottom: 16px;
}
.sp-hero-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--gold); }
.sp-hero-title {
  font-family: var(--font-display);
  font-size: 32px;
  color: #fff;
  font-weight: 600;
  line-height: 1.2;
  margin-bottom: 12px;
  max-width: 520px;
}
.sp-hero-sub {
  font-size: 14px;
  color: rgba(255,255,255,0.5);
  font-weight: 300;
  line-height: 1.7;
  max-width: 480px;
  margin-bottom: 24px;
}
.sp-hero-cta-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.sp-hero-btn-primary {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--green); color: #fff;
  border: none; border-radius: 7px;
  padding: 11px 20px;
  font-family: var(--font-body); font-size: 13px; font-weight: 500;
  text-decoration: none; cursor: pointer;
  transition: background 0.15s;
  white-space: nowrap;
}
.sp-hero-btn-primary:hover { background: #155f30; color: #fff; }
.sp-hero-btn-secondary {
  display: inline-flex; align-items: center; gap: 7px;
  background: rgba(255,255,255,0.08);
  border: 0.5px solid rgba(255,255,255,0.2);
  color: rgba(255,255,255,0.75);
  border-radius: 7px;
  padding: 11px 20px;
  font-family: var(--font-body); font-size: 13px; font-weight: 400;
  text-decoration: none; cursor: pointer;
  transition: background 0.15s;
  white-space: nowrap;
}
.sp-hero-btn-secondary:hover { background: rgba(255,255,255,0.14); color: #fff; }

/* ── 2. STATS ── */
.sp-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
}
.sp-stat {
  padding: 20px 16px;
  text-align: center;
  border-right: 0.5px solid var(--border);
}
.sp-stat:last-child { border-right: none; }
.sp-stat-val {
  font-family: var(--font-display);
  font-size: 24px; font-weight: 600;
  color: var(--dark); margin-bottom: 4px; line-height: 1;
}
.sp-stat-val.accent { color: var(--gold); }
.sp-stat-lbl { font-size: 11px; color: var(--muted); font-weight: 300; line-height: 1.4; }

/* ── 3. FEATURES ── */
.sp-section-pad { padding: 24px 28px; }
.sp-eyebrow {
  font-size: 10px; font-weight: 500;
  letter-spacing: 0.1em; text-transform: uppercase;
  color: var(--blue); margin-bottom: 6px;
}
.sp-section-title {
  font-family: var(--font-display);
  font-size: 20px; color: var(--dark); font-weight: 600;
  margin-bottom: 16px;
}
.sp-features {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.sp-feature {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px;
  background: #f9f8f6; border-radius: 8px;
}
.sp-feature-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--green); flex-shrink: 0;
}
.sp-feature-text { font-size: 13px; color: var(--dark); font-weight: 400; }

/* ── PROMO BANNER ── */
.sp-promo {
  background: rgba(228,177,35,0.08);
  border: 0.5px solid rgba(228,177,35,0.25);
  border-radius: 8px;
  padding: 13px 18px;
  display: flex; align-items: center; gap: 10px;
  margin: 4px 4px 0;
}
.sp-promo-icon {
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(228,177,35,0.15);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.sp-promo-text { font-size: 12px; color: #7a5f00; line-height: 1.55; }
.sp-promo-text strong { color: var(--dark); }

/* ── 4. FORK ── */
.sp-fork-label {
  text-align: center;
  font-size: 10px; color: var(--muted);
  text-transform: uppercase; letter-spacing: .1em;
  padding: 4px 0 0;
}
.sp-fork-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* Agent card */
.sp-agent-card {
  background: var(--dark);
  border-radius: 12px;
  padding: 24px;
  display: flex; flex-direction: column;
  position: relative; overflow: hidden;
}
.sp-agent-card::before {
  content: '';
  position: absolute; top: -40px; right: -40px;
  width: 160px; height: 160px; border-radius: 50%;
  border: 1px solid rgba(228,177,35,0.1);
  pointer-events: none;
}
.sp-free-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(228,177,35,0.1);
  border: 0.5px solid rgba(228,177,35,0.3);
  border-radius: 20px; padding: 4px 10px;
  margin-bottom: 18px; width: fit-content;
}
.sp-free-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--gold); }
.sp-free-txt { font-size: 10px; font-weight: 500; color: var(--gold); letter-spacing: 0.08em; text-transform: uppercase; }

.sp-agent-profile { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.sp-agent-photo {
  width: 56px; height: 56px; border-radius: 50%;
  border: 2px solid var(--gold);
  overflow: hidden; flex-shrink: 0;
  background: #2d2925;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: var(--gold);
}
.sp-agent-photo img { width:100%;height:100%;object-fit:cover;object-position:center top;border-radius:50%;display:block; }
.sp-agent-name { font-family: var(--font-display); font-size: 17px; color: #fff; font-weight: 600; line-height: 1.2; }
.sp-agent-title-sm { font-size: 11px; color: rgba(255,255,255,0.4); font-weight: 300; margin-top: 2px; }
.sp-agent-badges { display: flex; align-items: center; gap: 6px; margin-top: 5px; }
.sp-badge-remax { background: var(--remax); color: #fff; font-size: 8px; font-weight: 700; letter-spacing: 0.1em; padding: 2px 6px; border-radius: 3px; }
.sp-badge-rating { font-size: 11px; color: var(--gold); }
.sp-agent-desc { font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 300; line-height: 1.65; margin-bottom: 18px; }

.sp-intent-label { font-size: 9px; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
.sp-intent-row { display: flex; gap: 6px; margin-bottom: 16px; }
.sp-intent-btn {
  flex: 1; padding: 8px 6px;
  background: rgba(255,255,255,0.05);
  border: 0.5px solid rgba(255,255,255,0.12);
  border-radius: 7px;
  font-family: var(--font-body); font-size: 11px;
  color: rgba(255,255,255,0.6);
  cursor: pointer; text-align: center;
  transition: all 0.15s; line-height: 1.3;
}
.sp-intent-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #fff; }
.sp-intent-btn.selected { background: rgba(228,177,35,0.15); border-color: rgba(228,177,35,0.4); color: var(--gold); }

.sp-contact-actions { display: flex; flex-direction: column; gap: 8px; margin-top: auto; }
.sp-btn-text {
  width: 100%; height: 44px;
  background: var(--blue); border: none; border-radius: 7px;
  color: #fff; font-family: var(--font-body); font-size: 13px; font-weight: 500;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  text-decoration: none; transition: background 0.15s;
}
.sp-btn-text:hover { background: #1a90c8; color: #fff; }
.sp-btn-email {
  width: 100%; height: 38px;
  background: rgba(255,255,255,0.06);
  border: 0.5px solid rgba(255,255,255,0.14);
  border-radius: 7px; color: rgba(255,255,255,0.65);
  font-family: var(--font-body); font-size: 12px;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  text-decoration: none; transition: background 0.15s;
}
.sp-btn-email:hover { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9); }
.sp-call-link {
  text-align: center; font-size: 11px;
  color: rgba(255,255,255,0.3); text-decoration: none;
  transition: color 0.15s; padding: 4px 0; display: block;
}
.sp-call-link:hover { color: rgba(255,255,255,0.6); }

/* Subscribe card */
.sp-sub-card {
  background: var(--card);
  border: 0.5px solid var(--border);
  border-radius: 12px;
  padding: 24px;
  display: flex; flex-direction: column;
}
.sp-sub-eyebrow { font-size: 10px; font-weight: 500; color: var(--muted); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px; }
.sp-sub-title { font-family: var(--font-display); font-size: 17px; color: var(--dark); font-weight: 600; margin-bottom: 4px; }
.sp-sub-desc { font-size: 12px; color: var(--muted); font-weight: 300; line-height: 1.6; margin-bottom: 18px; }

.sp-plans { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
.sp-plan {
  display: flex; align-items: center; justify-content: space-between;
  border: 0.5px solid var(--border); border-radius: 8px;
  padding: 12px 15px; cursor: pointer; position: relative;
  transition: border-color 0.15s, background 0.15s;
}
.sp-plan:hover { border-color: #999; }
.sp-plan.sp-active { border: 1.5px solid var(--dark); background: #fafafa; }
.sp-plan-tag {
  position: absolute; top: -9px; left: 13px;
  background: var(--dark); color: var(--gold);
  font-size: 9px; font-weight: 600; letter-spacing: 0.06em;
  padding: 2px 8px; border-radius: 3px;
}
.sp-plan-name { font-size: 13px; font-weight: 500; color: var(--dark); }
.sp-plan-note { font-size: 10px; color: var(--muted); font-weight: 300; margin-top: 1px; }
.sp-plan-price { font-size: 22px; font-weight: 600; color: var(--dark); }
.sp-plan-per { font-size: 10px; color: var(--muted); margin-left: 1px; }
.sp-plan-save { font-size: 10px; color: var(--green); font-weight: 500; margin-left: 6px; }

.sp-btn-subscribe {
  width: 100%; height: 44px;
  background: var(--green); border: none; border-radius: 7px;
  color: #fff; font-family: var(--font-body);
  font-size: 13px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase;
  cursor: pointer; margin-bottom: 8px;
  display: flex; align-items: center; justify-content: center;
  text-decoration: none;
  transition: background 0.15s, transform 0.1s;
}
.sp-btn-subscribe:hover { background: #155f30; color: #fff; }
.sp-btn-subscribe:active { transform: scale(0.99); }
.sp-sub-fine { font-size: 10px; color: #ccc; text-align: center; line-height: 1.5; }

/* ── 5. AUTHORITY ── */
.sp-authority { padding: 22px 28px; display: flex; align-items: center; gap: 20px; }
.sp-auth-photo {
  width: 68px; height: 68px; border-radius: 50%;
  border: 2px solid var(--gold); overflow: hidden; flex-shrink: 0;
  background: #2d2925;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 700; color: var(--gold);
}
.sp-auth-photo img { width:100%;height:100%;object-fit:cover;object-position:center top;border-radius:50%;display:block; }
.sp-auth-info { flex: 1; }
.sp-auth-name { font-family: var(--font-display); font-size: 18px; color: var(--dark); font-weight: 600; margin-bottom: 2px; }
.sp-auth-title { font-size: 12px; color: var(--muted); font-weight: 300; margin-bottom: 8px; }
.sp-auth-badges { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.sp-auth-badge { background: #f0ede8; border-radius: 4px; padding: 3px 9px; font-size: 10px; color: #555; }
.sp-auth-badge-remax { background: var(--remax); color: #fff; font-size: 9px; font-weight: 700; letter-spacing: 0.1em; padding: 3px 8px; border-radius: 4px; }
.sp-auth-actions { display: flex; gap: 8px; flex-shrink: 0; }
.sp-auth-btn-call {
  height: 36px; padding: 0 16px;
  background: var(--blue); border: none; border-radius: 6px;
  color: #fff; font-family: var(--font-body); font-size: 12px; font-weight: 500;
  cursor: pointer; display: flex; align-items: center; gap: 5px;
  text-decoration: none; white-space: nowrap; transition: background 0.15s;
}
.sp-auth-btn-call:hover { background: #1a90c8; color: #fff; }
.sp-auth-btn-sms {
  height: 36px; padding: 0 16px;
  background: rgba(34,170,226,0.08);
  border: 0.5px solid rgba(34,170,226,0.3);
  border-radius: 6px; color: var(--blue);
  font-family: var(--font-body); font-size: 12px; font-weight: 500;
  cursor: pointer; display: flex; align-items: center; gap: 5px;
  text-decoration: none; white-space: nowrap; transition: background 0.15s;
}
.sp-auth-btn-sms:hover { background: rgba(34,170,226,0.14); color: var(--blue); }

/* ── 6. FAQ ── */
.sp-faq-list { padding: 0 28px; }
.sp-faq-title { font-family: var(--font-display); font-size: 20px; color: var(--dark); font-weight: 600; padding: 22px 0 16px; }
.sp-faq-item { border-top: 0.5px solid var(--border); cursor: pointer; overflow: hidden; }
.sp-faq-item:last-child { border-bottom: 0.5px solid var(--border); }
.sp-faq-q {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 0; font-size: 13px; font-weight: 500; color: var(--dark);
  user-select: none;
}
.sp-faq-icon {
  width: 20px; height: 20px; border-radius: 50%;
  background: #f0ede8;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 14px; color: var(--muted);
  transition: transform 0.2s, background 0.15s;
}
.sp-faq-item.sp-open .sp-faq-icon { transform: rotate(45deg); background: var(--dark); color: #fff; }
.sp-faq-a {
  font-size: 12px; color: var(--muted); font-weight: 300;
  line-height: 1.7; max-height: 0; overflow: hidden;
  transition: max-height 0.3s ease, padding 0.2s;
}
.sp-faq-item.sp-open .sp-faq-a { max-height: 200px; padding-bottom: 14px; }

/* ── INTENT MODAL ── */
.sp-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(35,31,32,0.65); backdrop-filter: blur(3px);
  z-index: 9999; align-items: center; justify-content: center; padding: 20px;
}
.sp-overlay.sp-overlay-open { display: flex; }
.sp-modal {
  background: var(--dark); border-radius: 14px;
  width: 100%; max-width: 400px; overflow: hidden;
  animation: spSlideUp 0.22s ease;
}
@keyframes spSlideUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
.sp-modal-header { padding: 20px 22px 16px; border-bottom: 0.5px solid rgba(255,255,255,0.08); position: relative; }
.sp-modal-close {
  position: absolute; top: 16px; right: 16px;
  width: 26px; height: 26px; border-radius: 50%;
  background: rgba(255,255,255,0.08); border: none;
  color: rgba(255,255,255,0.5); font-size: 14px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.sp-modal-close:hover { background: rgba(255,255,255,0.14); }
.sp-modal-agent-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.sp-modal-photo {
  width: 40px; height: 40px; border-radius: 50%;
  border: 1.5px solid var(--gold); overflow: hidden; flex-shrink: 0;
  background: #2d2925; display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; color: var(--gold);
}
.sp-modal-photo img { width:100%;height:100%;object-fit:cover;object-position:center top;border-radius:50%;display:block; }
.sp-modal-hname { font-size: 14px; font-weight: 500; color: #fff; }
.sp-modal-hsub { font-size: 10px; color: rgba(255,255,255,0.38); font-weight: 300; }
.sp-modal-q { font-family: var(--font-display); font-size: 17px; color: #fff; font-weight: 600; margin-bottom: 3px; }
.sp-modal-sub { font-size: 11px; color: rgba(255,255,255,0.45); font-weight: 300; }
.sp-modal-body { padding: 18px 22px 22px; }
.sp-modal-intents { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }
.sp-modal-intent {
  display: flex; align-items: center; gap: 10px;
  background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.1);
  border-radius: 8px; padding: 12px 14px;
  cursor: pointer; transition: all 0.15s;
}
.sp-modal-intent:hover { background: rgba(255,255,255,0.09); border-color: rgba(255,255,255,0.18); }
.sp-modal-intent.sp-selected { background: rgba(228,177,35,0.12); border-color: rgba(228,177,35,0.35); }
.sp-modal-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sp-modal-icon svg { width: 14px; height: 14px; }
.sp-modal-intent-title { font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 1px; }
.sp-modal-intent-desc { font-size: 10px; color: rgba(255,255,255,0.4); font-weight: 300; }
.sp-modal-actions { display: flex; flex-direction: column; gap: 7px; }
.sp-modal-btn-text {
  width: 100%; height: 44px;
  background: var(--blue); border: none; border-radius: 7px;
  color: #fff; font-family: var(--font-body); font-size: 13px; font-weight: 500;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  text-decoration: none; transition: background 0.15s;
}
.sp-modal-btn-text:hover { background: #1a90c8; color: #fff; }
.sp-modal-btn-email {
  width: 100%; height: 38px;
  background: rgba(255,255,255,0.06); border: 0.5px solid rgba(255,255,255,0.12);
  border-radius: 7px; color: rgba(255,255,255,0.6);
  font-family: var(--font-body); font-size: 12px;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  text-decoration: none; transition: background 0.15s;
}
.sp-modal-btn-email:hover { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9); }
.sp-modal-call-link {
  text-align: center; font-size: 11px; color: rgba(255,255,255,0.28);
  text-decoration: none; padding: 4px 0; display: block; transition: color 0.15s;
}
.sp-modal-call-link:hover { color: rgba(255,255,255,0.55); }
.sp-modal-hint { text-align: center; font-size: 10px; color: rgba(255,255,255,0.25); margin-top: 10px; line-height: 1.5; }

/* ── HERO TWO-COLUMN ── */
.sp-hero-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  align-items: start;
}
.sp-hero-left { display: flex; flex-direction: column; }
.sp-hero-remax-row {
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 14px;
}
.sp-hero-remax-badge {
  background: var(--remax); color: #fff;
  font-size: 9px; font-weight: 700; letter-spacing: 0.1em;
  padding: 3px 8px; border-radius: 3px;
  text-transform: uppercase;
}
.sp-hero-remax-label {
  font-size: 11px; color: rgba(255,255,255,0.45); font-weight: 300;
}
.sp-hero-urgency {
  margin-top: 16px;
  background: rgba(228,177,35,0.1);
  border: 0.5px solid rgba(228,177,35,0.3);
  border-radius: 7px;
  padding: 10px 14px;
  display: flex; align-items: center; gap: 8px;
}
.sp-hero-urgency-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--gold); flex-shrink: 0;
  animation: urgencyPulse 1.8s ease-in-out infinite;
}
@keyframes urgencyPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.35; }
}
.sp-hero-urgency-text {
  font-size: 12px; color: rgba(255,255,255,0.7); font-weight: 300; line-height: 1.4;
}
.sp-hero-urgency-text strong { color: var(--gold); font-weight: 500; }

/* Property card (right column) */
.sp-hero-prop-card {
  background: rgba(255,255,255,0.06);
  border: 0.5px solid rgba(255,255,255,0.12);
  border-radius: 10px;
  overflow: hidden;
}
.sp-hero-prop-img {
  width: 100%; height: 110px;
  background: linear-gradient(135deg, #2d2925 0%, #1a3a4a 100%);
  display: flex; align-items: center; justify-content: center;
  position: relative;
}
.sp-hero-prop-img-icon {
  width: 36px; height: 36px; opacity: 0.3;
}
.sp-hero-prop-tag {
  position: absolute; top: 8px; left: 8px;
  background: rgba(35,31,32,0.7);
  border: 0.5px solid rgba(255,255,255,0.15);
  border-radius: 4px;
  padding: 2px 7px;
  font-size: 9px; color: rgba(255,255,255,0.6); font-weight: 500; letter-spacing: 0.08em;
  text-transform: uppercase;
}
.sp-hero-prop-body { padding: 12px 14px; }
.sp-hero-prop-name {
  font-size: 13px; font-weight: 500; color: #fff;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: 2px;
}
.sp-hero-prop-addr {
  font-size: 11px; color: rgba(255,255,255,0.4); font-weight: 300;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: 10px;
}
.sp-hero-prop-price-row {
  display: flex; align-items: center; justify-content: space-between;
}
.sp-hero-prop-price-label { font-size: 10px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.08em; }
.sp-hero-prop-price-val {
  font-size: 18px; font-weight: 600; color: #fff;
  filter: blur(6px); user-select: none;
}
.sp-hero-prop-lock {
  display: flex; align-items: center; gap: 5px;
  font-size: 10px; color: var(--gold); font-weight: 400;
}

/* Feature checklist */
.sp-checklist { display: flex; flex-direction: column; gap: 7px; margin-bottom: 18px; }
.sp-checklist-item { display: flex; align-items: center; gap: 9px; font-size: 12px; color: var(--dark); }
.sp-checklist-icon {
  width: 18px; height: 18px; border-radius: 50%;
  background: var(--green);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.sp-checklist-icon svg { width: 10px; height: 10px; }

/* Testimonial card */
.sp-testimonial {
  background: var(--card);
  border-radius: 12px;
  padding: 22px 24px;
}
.sp-testi-stars { color: #fbbc04; font-size: 15px; letter-spacing: 1px; margin-bottom: 10px; }
.sp-testi-quote {
  font-size: 13px; color: var(--dark); font-weight: 400; line-height: 1.7;
  font-style: italic; margin-bottom: 14px;
}
.sp-testi-author { display: flex; align-items: center; gap: 10px; }
.sp-testi-avatar {
  width: 38px; height: 38px; border-radius: 50%;
  background: #e8e4e0;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 600; color: #555;
  flex-shrink: 0;
}
.sp-testi-name { font-size: 13px; font-weight: 500; color: var(--dark); }
.sp-testi-via { font-size: 10px; color: var(--muted); font-weight: 300; margin-top: 1px; }
.sp-google-g {
  margin-left: auto; flex-shrink: 0;
  display: flex; align-items: center; gap: 4px;
  font-size: 10px; color: var(--muted);
}

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
  .sp-page { padding: 0 12px; }
  .sp-hero { padding: 24px 20px; }
  .sp-hero-title { font-size: 26px; }
  .sp-hero-cols { grid-template-columns: 1fr; }
  .sp-hero-prop-card { display: none; }
  .sp-stats { grid-template-columns: repeat(2,1fr); }
  .sp-stat:nth-child(2) { border-right: none; }
  .sp-stat:nth-child(1), .sp-stat:nth-child(2) { border-bottom: 0.5px solid var(--border); }
  .sp-features { grid-template-columns: 1fr; }
  .sp-fork-grid { grid-template-columns: 1fr; }
  .sp-authority { flex-direction: column; align-items: flex-start; padding: 20px; }
  .sp-auth-actions { width: 100%; }
  .sp-auth-btn-call, .sp-auth-btn-sms { flex: 1; justify-content: center; }
  .sp-intent-row { flex-direction: column; }
  .sp-hero-cta-row { flex-direction: column; }
}

/* ── Stripe Pricing Table Modal ── */
#spPricingOverlay {
  display: none; position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.65); align-items: center; justify-content: center;
  padding: 16px;
}
#spPricingOverlay.sp-overlay-open { display: flex; }
.sp-pricing-modal {
  background: #fff; border-radius: 16px; width: 100%; max-width: 860px;
  max-height: 90vh; overflow-y: auto; position: relative;
  box-shadow: 0 24px 64px rgba(0,0,0,0.25);
}
.sp-pricing-modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px 12px; border-bottom: 1px solid #eee;
}
.sp-pricing-modal-title { font-size: 15px; font-weight: 600; color: #1a1a1a; }
.sp-pricing-modal-close {
  background: none; border: none; font-size: 22px; cursor: pointer;
  color: #666; line-height: 1; padding: 0 4px;
}
.sp-pricing-modal-body { padding: 20px; }
</style>

<!-- ══ INTENT CONTACT MODAL ══ -->
<div class="sp-overlay" id="spIntentOverlay">
  <div class="sp-modal">
    <div class="sp-modal-header">
      <button class="sp-modal-close" onclick="spCloseIntent()">&#215;</button>
      <div class="sp-modal-agent-row">
        <div class="sp-modal-photo">
          @if($subscriptionAgentImage)
          <img src="{{ $subscriptionAgentImage }}" alt="{{ $subscriptionAgentName }}"
               onerror="this.style.display='none';this.parentElement.textContent='{{ $subscriptionAgentInitials }}'">
          @else
          {{ $subscriptionAgentInitials }}
          @endif
        </div>
        <div>
          <div class="sp-modal-hname">{{ $subscriptionAgentName }}</div>
          <div class="sp-modal-hsub">{{ $subscriptionAgentTitle }} &middot; {{ $subscriptionAgentAgency }} &middot; &#9733; 4.9</div>
        </div>
      </div>
      <div class="sp-modal-q">What are you looking for?</div>
      <div class="sp-modal-sub">One tap &mdash; so {{ $subscriptionAgentFirstName }} knows how to help you.</div>
    </div>
    <div class="sp-modal-body">
      <div class="sp-modal-intents">
        <div class="sp-modal-intent" onclick="spSelectIntent(this,'buying')" id="sp-mi-buying">
          <div class="sp-modal-icon" style="background:#1a3a4a;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#22aae2" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </div>
          <div>
            <div class="sp-modal-intent-title">I'm looking to buy</div>
            <div class="sp-modal-intent-desc">Searching for a home or investment property</div>
          </div>
        </div>
        <div class="sp-modal-intent" onclick="spSelectIntent(this,'selling')" id="sp-mi-selling">
          <div class="sp-modal-icon" style="background:#1a3028;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="2" stroke-linecap="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
          </div>
          <div>
            <div class="sp-modal-intent-title">I'm looking to sell</div>
            <div class="sp-modal-intent-desc">Researching what my property is worth</div>
          </div>
        </div>
        <div class="sp-modal-intent" onclick="spSelectIntent(this,'research')" id="sp-mi-research">
          <div class="sp-modal-icon" style="background:#2d2510;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#e4b123" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </div>
          <div>
            <div class="sp-modal-intent-title">Just researching</div>
            <div class="sp-modal-intent-desc">Market data, investment analysis</div>
          </div>
        </div>
      </div>
      <div class="sp-modal-actions">
        <a href="sms:{{ $subscriptionAgentSms }}" class="sp-modal-btn-text" id="sp-modal-text-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="#fff"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
          Text {{ $subscriptionAgentFirstName }}
        </a>
        <a href="mailto:{{ $subscriptionAgentEmail }}?subject=Property%20Enquiry" class="sp-modal-btn-email" id="sp-modal-email-btn">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          Email {{ $subscriptionAgentFirstName }}
        </a>
        <a href="tel:{{ $subscriptionAgentTel }}" class="sp-modal-call-link">or call {{ $subscriptionAgentPhone }}</a>
      </div>
      <div class="sp-modal-hint" id="sp-modal-hint">Select what you're looking for above &mdash; {{ $subscriptionAgentFirstName }} will reach out with the right info.</div>
    </div>
  </div>
</div>

<!-- ══ PAGE ══ -->
<div class="sp-wrap">
<div class="sp-page">

  <!-- 1. HERO -->
  <div class="sp-card-dark">
    <div class="sp-hero">
      <div class="sp-hero-cols">
        <!-- Left column -->
        <div class="sp-hero-left">
          <div class="sp-hero-remax-row">
            <span class="sp-hero-remax-badge">RE/MAX</span>
            <span class="sp-hero-remax-label">{{ $subscriptionAgentName }} &middot; {{ $subscriptionAgentTitle }}</span>
          </div>
          <div class="sp-hero-eyebrow">
            <div class="sp-hero-dot"></div>
            Your free trial has ended
          </div>
          <h1 class="sp-hero-title">You saw the data.<br>Now keep the access.</h1>
          <p class="sp-hero-sub">The sold prices, floor plans and strata documents you found aren&rsquo;t going anywhere. Neither should you &mdash; subscribe from $0.54&thinsp;/&thinsp;day, or work with {{ $subscriptionAgentFirstName }} for free.</p>
          <div class="sp-hero-cta-row">
            <a href="#sp-subscribe" class="sp-hero-btn-primary">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
              Subscribe now
            </a>
            <a href="sms:{{ $subscriptionAgentSms }}" class="sp-hero-btn-secondary">
              Text {{ $subscriptionAgentFirstName }} &mdash; get free access
            </a>
          </div>
          @if(isset($expired_at) && $expired_at)
          @php
            $diffSeconds = time() - $expired_at;
            $diffHours   = intval($diffSeconds / 3600);
            $diffDays    = intval($diffSeconds / 86400);
            $urgencyLabel = $diffDays >= 1
              ? ($diffDays === 1 ? '1 day' : $diffDays . ' days') . ' ago'
              : ($diffHours <= 1 ? 'just now' : $diffHours . ' hours ago');
          @endphp
          <div class="sp-hero-urgency">
            <div class="sp-hero-urgency-dot"></div>
            <div class="sp-hero-urgency-text">Your free access expired <strong>{{ $urgencyLabel }}</strong>. Subscribe to restore access instantly.</div>
          </div>
          @else
          <div class="sp-hero-urgency">
            <div class="sp-hero-urgency-dot"></div>
            <div class="sp-hero-urgency-text">Your free trial has ended. <strong>Subscribe now</strong> to restore full access instantly.</div>
          </div>
          @endif
        </div>
        <!-- Right column: last-viewed property card -->
        @if(isset($last_property) && $last_property)
        <div class="sp-hero-prop-card">
          <div class="sp-hero-prop-img">
            <div class="sp-hero-prop-tag">Last viewed</div>
            <svg class="sp-hero-prop-img-icon" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </div>
          <div class="sp-hero-prop-body">
            <div class="sp-hero-prop-name">{{ $last_property['name'] }}</div>
            @if(!empty($last_property['address']))
            <div class="sp-hero-prop-addr">{{ $last_property['address'] }}</div>
            @endif
            <div class="sp-hero-prop-price-row">
              <div>
                <div class="sp-hero-prop-price-label">Sold price</div>
                <div class="sp-hero-prop-price-val">{{ $last_property['price'] }}</div>
              </div>
              <div class="sp-hero-prop-lock">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Subscribe to unlock
              </div>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>

  <!-- 2. STATS -->
  <div class="sp-card">
    <div class="sp-stats">
      <div class="sp-stat">
        <div class="sp-stat-val accent">157k+</div>
        <div class="sp-stat-lbl">Registered users trust this platform</div>
      </div>
      <div class="sp-stat">
        <div class="sp-stat-val accent">6,000+</div>
        <div class="sp-stat-lbl">Visitors every single day</div>
      </div>
      <div class="sp-stat">
        <div class="sp-stat-val">#1 &amp; #2</div>
        <div class="sp-stat-lbl">Google ranking for Vancouver condos</div>
      </div>
      <div class="sp-stat">
        <div class="sp-stat-val">1,000+</div>
        <div class="sp-stat-lbl">Buildings with strata docs &amp; sold history</div>
      </div>
    </div>
  </div>

  <!-- 3. WHAT YOU GET -->
  <div class="sp-card">
    <div class="sp-section-pad">
      <div class="sp-eyebrow">Everything included</div>
      <h2 class="sp-section-title">What you unlock with full access</h2>
      <div class="sp-features">
        <div class="sp-feature"><div class="sp-feature-dot"></div><span class="sp-feature-text">Sold prices &mdash; every building &amp; listing</span></div>
        <div class="sp-feature"><div class="sp-feature-dot"></div><span class="sp-feature-text">Floor plans</span></div>
        <div class="sp-feature"><div class="sp-feature-dot"></div><span class="sp-feature-text">Strata documents &amp; bylaws</span></div>
        <div class="sp-feature"><div class="sp-feature-dot"></div><span class="sp-feature-text">Depreciation reports</span></div>
        <div class="sp-feature"><div class="sp-feature-dot"></div><span class="sp-feature-text">Market insights &amp; trends</span></div>
        <div class="sp-feature"><div class="sp-feature-dot"></div><span class="sp-feature-text">Unlimited property access</span></div>
      </div>
    </div>
  </div>

  <!-- PROMO BANNER -->
  <div class="sp-promo">
    <div class="sp-promo-icon">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e4b123" stroke-width="2" stroke-linecap="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
    </div>
    <div class="sp-promo-text">
      Not sure which path? <strong>{{ $subscriptionAgentFirstName }} works with buyers and sellers across Metro Vancouver</strong> &mdash; no pressure, no obligation to reach out and ask.
    </div>
  </div>

  <!-- 4. FORK -->
  <div class="sp-fork-label">Choose how you want to continue</div>

  <div class="sp-fork-grid">

    <!-- Agent path -->
    <div class="sp-agent-card">
      <div class="sp-free-badge">
        <div class="sp-free-dot"></div>
        <span class="sp-free-txt">Unlimited &middot; Free</span>
      </div>
      <div class="sp-agent-profile">
        <div class="sp-agent-photo">
          @if($subscriptionAgentImage)
          <img src="{{ $subscriptionAgentImage }}" alt="{{ $subscriptionAgentName }}"
               onerror="this.style.display='none';this.parentElement.textContent='{{ $subscriptionAgentInitials }}'">
          @else
          {{ $subscriptionAgentInitials }}
          @endif
        </div>
        <div>
          <div class="sp-agent-name">{{ $subscriptionAgentName }}</div>
          <div class="sp-agent-title-sm">{{ $subscriptionAgentTitle }}</div>
          <div class="sp-agent-badges">
            <div class="sp-badge-remax">RE/MAX</div>
            <div class="sp-badge-rating">&#9733; 4.9 &middot; 39 reviews</div>
          </div>
        </div>
      </div>
      <p class="sp-agent-desc">Clients working with {{ $subscriptionAgentFirstName }} get unlimited free access &mdash; sold prices, strata docs, floor plans, everything. No subscription needed.</p>
      <div class="sp-intent-label">What are you looking for?</div>
      <div class="sp-intent-row">
        <button class="sp-intent-btn" onclick="spQuickIntent(this,'buying')">I&rsquo;m buying</button>
        <button class="sp-intent-btn" onclick="spQuickIntent(this,'selling')">I&rsquo;m selling</button>
        <button class="sp-intent-btn" onclick="spQuickIntent(this,'research')">Researching</button>
      </div>
      <div class="sp-contact-actions">
        <a href="sms:{{ $subscriptionAgentSms }}" class="sp-btn-text" id="sp-card-text-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="#fff"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
          Text {{ $subscriptionAgentFirstName }} &mdash; Get free access
        </a>
        <a href="mailto:{{ $subscriptionAgentEmail }}?subject=Free%20Access%20Request" class="sp-btn-email" id="sp-card-email-btn">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          Email {{ $subscriptionAgentFirstName }}
        </a>
        <a href="tel:{{ $subscriptionAgentTel }}" class="sp-call-link">or call {{ $subscriptionAgentPhone }}</a>
        <button onclick="spOpenIntent()" style="background:none;border:none;cursor:pointer;padding:6px 0;font-family:var(--font-body);font-size:11px;color:rgba(255,255,255,0.25);text-align:center;width:100%;transition:color 0.15s;" onmouseover="this.style.color='rgba(255,255,255,0.5)'" onmouseout="this.style.color='rgba(255,255,255,0.25)'">
          More ways to reach {{ $subscriptionAgentFirstName }} &rarr;
        </button>
      </div>
    </div>

    <!-- Subscribe path -->
    <div class="sp-sub-card" id="sp-subscribe">
      <div class="sp-sub-eyebrow">Have your own agent?</div>
      <div class="sp-sub-title">Subscribe for full access</div>
      <p class="sp-sub-desc">From $0.54/day. Cancel anytime. Instant access to everything the platform has.</p>
      <div class="sp-checklist">
        <div class="sp-checklist-item">
          <div class="sp-checklist-icon">
            <svg viewBox="0 0 12 12" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,6 5,9 10,3"/></svg>
          </div>
          Sold prices for every building &amp; listing
        </div>
        <div class="sp-checklist-item">
          <div class="sp-checklist-icon">
            <svg viewBox="0 0 12 12" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,6 5,9 10,3"/></svg>
          </div>
          Floor plans
        </div>
        <div class="sp-checklist-item">
          <div class="sp-checklist-icon">
            <svg viewBox="0 0 12 12" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,6 5,9 10,3"/></svg>
          </div>
          Strata documents &amp; bylaws
        </div>
        <div class="sp-checklist-item">
          <div class="sp-checklist-icon">
            <svg viewBox="0 0 12 12" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,6 5,9 10,3"/></svg>
          </div>
          Market insights &amp; trends
        </div>
      </div>
      <div class="sp-plans">

        <div class="sp-plan" onclick="spSelectPlan(this,'weekly')" data-stripe="https://buy.stripe.com/8x2aEY9R43gw5n27jq4Ni05?prefilled_email={{ urlencode($user->email) }}&client_reference_id={{ $user->uid }}">
          <div>
            <div class="sp-plan-name">Weekly</div>
            <div class="sp-plan-note">Cancel anytime</div>
          </div>
          <div style="text-align:right;">
            <span class="sp-plan-price">$14</span><span class="sp-plan-per">/wk</span>
          </div>
        </div>

        <div class="sp-plan sp-active" onclick="spSelectPlan(this,'monthly')" data-stripe="https://buy.stripe.com/cNi28s0guaIY5n27jq4Ni03?prefilled_email={{ urlencode($user->email) }}&client_reference_id={{ $user->uid }}">
          <div class="sp-plan-tag">Recommended</div>
          <div>
            <div class="sp-plan-name">Monthly</div>
            <div class="sp-plan-note">Cancel anytime</div>
          </div>
          <div style="text-align:right;">
            <span class="sp-plan-price">$28</span><span class="sp-plan-per">/mo</span>
          </div>
        </div>

        <div class="sp-plan" onclick="spSelectPlan(this,'yearly')" data-stripe="https://buy.stripe.com/3cI7sMe7kcR68zegU04Ni04?prefilled_email={{ urlencode($user->email) }}&client_reference_id={{ $user->uid }}">
          <div>
            <div class="sp-plan-name">Yearly</div>
            <div class="sp-plan-note">Best value</div>
          </div>
          <div style="text-align:right;">
            <span class="sp-plan-price">$197</span><span class="sp-plan-per">/yr</span>
            <span class="sp-plan-save">Save 41%</span>
          </div>
        </div>

      </div>
      <a href="#" class="sp-btn-subscribe" id="sp-subscribe-btn" onclick="spOpenPricingTable(event)">Subscribe now</a>
      <div class="sp-sub-fine">Risk-free &middot; Cancel anytime &middot; Instant access</div>
    </div>

  </div>

  <!-- 5. AUTHORITY -->
  <div class="sp-card">
    <div class="sp-authority">
      <div class="sp-auth-photo">
        @if($subscriptionAgentImage)
        <img src="{{ $subscriptionAgentImage }}" alt="{{ $subscriptionAgentName }}"
             onerror="this.style.display='none';this.parentElement.textContent='{{ $subscriptionAgentInitials }}'">
        @else
        {{ $subscriptionAgentInitials }}
        @endif
      </div>
      <div class="sp-auth-info">
        <div class="sp-auth-name">{{ $subscriptionAgentName }}</div>
        <div class="sp-auth-title">{{ $subscriptionAgentTitle }} &middot; {{ $subscriptionAgentAgency }}</div>
        <div class="sp-auth-badges">
          <div class="sp-auth-badge">&#9733; 4.9 &middot; 39 reviews</div>
          <div class="sp-auth-badge">Local market guidance</div>
          <div class="sp-auth-badge">BC Real Estate Licensed</div>
          <div class="sp-auth-badge-remax">RE/MAX</div>
        </div>
      </div>
      <div class="sp-auth-actions">
        <a href="tel:{{ $subscriptionAgentTel }}" class="sp-auth-btn-call">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
          Call {{ $subscriptionAgentFirstName }}
        </a>
        <a href="sms:{{ $subscriptionAgentSms }}" class="sp-auth-btn-sms">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="#22aae2"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
          Text {{ $subscriptionAgentFirstName }}
        </a>
      </div>
    </div>
  </div>

  <!-- TESTIMONIAL -->
  <div class="sp-testimonial">
    <div class="sp-testi-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
    <p class="sp-testi-quote">&ldquo;{{ $subscriptionAgentFirstName }} was incredibly helpful throughout our condo search. The sold price data on this platform gave us real confidence going into negotiations &mdash; we ended up paying below asking. Highly recommend both the site and {{ $subscriptionAgentFirstName }}.&rdquo;</p>
    <div class="sp-testi-author">
      <div class="sp-testi-avatar">MK</div>
      <div>
        <div class="sp-testi-name">M. Kaur</div>
        <div class="sp-testi-via">Verified buyer &middot; Vancouver</div>
      </div>
      <div class="sp-google-g">
        <svg width="14" height="14" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Google Review
      </div>
    </div>
  </div>

  <!-- 6. FAQ -->
  <div class="sp-card">
    <div class="sp-faq-list">
      <div class="sp-faq-title">Frequently asked questions</div>

      <div class="sp-faq-item" onclick="spToggleFaq(this)">
        <div class="sp-faq-q">How do I get free access if I work with {{ $subscriptionAgentFirstName }}? <div class="sp-faq-icon">+</div></div>
        <div class="sp-faq-a">Simply text or email {{ $subscriptionAgentFirstName }} using the buttons above and let {{ $subscriptionAgentFirstName }} know you&rsquo;d like free access. Clients actively working with {{ $subscriptionAgentName }} on a purchase or sale receive unlimited complimentary access to all platform data &mdash; sold prices, strata documents, floor plans and market insights.</div>
      </div>

      <div class="sp-faq-item" onclick="spToggleFaq(this)">
        <div class="sp-faq-q">Can I cancel my subscription anytime? <div class="sp-faq-icon">+</div></div>
        <div class="sp-faq-a">Yes, absolutely. There are no contracts or commitments. You can cancel your weekly, monthly or yearly subscription at any time. If you cancel a yearly plan partway through, you retain access until the end of your billing period.</div>
      </div>

      <div class="sp-faq-item" onclick="spToggleFaq(this)">
        <div class="sp-faq-q">How often is sold data updated? <div class="sp-faq-icon">+</div></div>
        <div class="sp-faq-a">Sold price data is updated regularly as transactions are recorded through the BC land title registry and MLS systems. Most sales appear within a few days of completion. Historical data goes back several years for most buildings in the Lower Mainland.</div>
      </div>

      <div class="sp-faq-item" onclick="spToggleFaq(this)">
        <div class="sp-faq-q">How is this different from REW.ca or Realtor.ca? <div class="sp-faq-icon">+</div></div>
        <div class="sp-faq-a">REW and Realtor.ca show active listings only &mdash; they don&rsquo;t display sold prices, strata documents, floor plans or building-specific history. BC Condos &amp; Homes is built specifically for the BC condo and townhouse market and is ranked #1 and #2 on Google for Vancouver condo searches, with over 157,000 registered users relying on it for real transaction data.</div>
      </div>

      <div class="sp-faq-item" onclick="spToggleFaq(this)">
        <div class="sp-faq-q">I don&rsquo;t have a realtor yet &mdash; where do I start? <div class="sp-faq-icon">+</div></div>
        <div class="sp-faq-a">Text or call {{ $subscriptionAgentFirstName }} directly &mdash; you&rsquo;ll get answers to questions about a property, neighbourhood or the market with no obligation. Clients who choose to work with {{ $subscriptionAgentFirstName }} on their purchase or sale get unlimited free access to the platform as part of the relationship.</div>
      </div>

    </div>
    <div style="height:24px;"></div>
  </div>

  <div style="height:20px;"></div>
</div>
</div>

<script>
(function() {
  var spSelectedIntent = null;

  window.spSelectPlan = function(el, key) {
    document.querySelectorAll('.sp-plan').forEach(function(p){ p.classList.remove('sp-active'); });
    el.classList.add('sp-active');
    var btn = document.getElementById('sp-subscribe-btn');
    if (btn && el.dataset.stripe) { btn.href = el.dataset.stripe; }
    if (el.dataset.stripe) { window.location.href = el.dataset.stripe; }
  };

  window.spQuickIntent = function(btn, intent) {
    btn.closest('.sp-intent-row').querySelectorAll('.sp-intent-btn').forEach(function(b){ b.classList.remove('selected'); });
    btn.classList.add('selected');
    spSelectedIntent = intent;
    spUpdateCardLinks(intent);
  };

  function spUpdateCardLinks(intent) {
    var labels = { buying: 'Buying', selling: 'Selling', research: 'Researching' };
    var lbl = labels[intent] || '';
    var sms = encodeURIComponent("Hi {{ $subscriptionAgentFirstName }}, I'm interested in BC Condos data — " + lbl);
    var mail = encodeURIComponent('Free Access Request — ' + lbl);
    var textBtn = document.getElementById('sp-card-text-btn');
    var emailBtn = document.getElementById('sp-card-email-btn');
    if (textBtn) textBtn.href = 'sms:{{ $subscriptionAgentSms }}?body=' + sms;
    if (emailBtn) emailBtn.href = 'mailto:{{ $subscriptionAgentEmail }}?subject=' + mail;
  }

  window.spToggleFaq = function(el) {
    var isOpen = el.classList.contains('sp-open');
    document.querySelectorAll('.sp-faq-item').forEach(function(i){ i.classList.remove('sp-open'); });
    if (!isOpen) el.classList.add('sp-open');
  };

  window.spOpenIntent = function() {
    document.getElementById('spIntentOverlay').classList.add('sp-overlay-open');
    document.body.style.overflow = 'hidden';
  };

  window.spCloseIntent = function() {
    document.getElementById('spIntentOverlay').classList.remove('sp-overlay-open');
    document.body.style.overflow = '';
  };

  window.spSelectIntent = function(el, intent) {
    document.querySelectorAll('.sp-modal-intent').forEach(function(i){ i.classList.remove('sp-selected'); });
    el.classList.add('sp-selected');
    spSelectedIntent = intent;
    var labels = { buying: 'Buying', selling: 'Selling', research: 'Researching' };
    var lbl = labels[intent] || '';
    var sms = encodeURIComponent("Hi {{ $subscriptionAgentFirstName }}, I'm interested in BC Condos data — " + lbl);
    var mail = encodeURIComponent('Property Enquiry — ' + lbl);
    var textBtn = document.getElementById('sp-modal-text-btn');
    var emailBtn = document.getElementById('sp-modal-email-btn');
    if (textBtn) textBtn.href = 'sms:{{ $subscriptionAgentSms }}?body=' + sms;
    if (emailBtn) emailBtn.href = 'mailto:{{ $subscriptionAgentEmail }}?subject=' + mail;
    var hint = document.getElementById('sp-modal-hint');
    if (hint) hint.style.display = 'none';
  };

  document.getElementById('spIntentOverlay').addEventListener('click', function(e) {
    if (e.target === this) spCloseIntent();
  });

  window.spOpenPricingTable = function(e) {
    if (e) e.preventDefault();
    document.getElementById('spPricingOverlay').classList.add('sp-overlay-open');
    document.body.style.overflow = 'hidden';
  };

  window.spClosePricingTable = function() {
    document.getElementById('spPricingOverlay').classList.remove('sp-overlay-open');
    document.body.style.overflow = '';
  };

  document.getElementById('spPricingOverlay').addEventListener('click', function(e) {
    if (e.target === this) spClosePricingTable();
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { spCloseIntent(); spClosePricingTable(); }
  });

})();
</script>

<!-- ══ STRIPE PRICING TABLE MODAL ══ -->
<div id="spPricingOverlay">
  <div class="sp-pricing-modal">
    <div class="sp-pricing-modal-header">
      <span class="sp-pricing-modal-title">Choose your plan</span>
      <button class="sp-pricing-modal-close" onclick="spClosePricingTable()" aria-label="Close">&times;</button>
    </div>
    <div class="sp-pricing-modal-body">
      <stripe-pricing-table
        pricing-table-id="prctbl_1TOoB7JMQ9rLXPTOEEcBYfYt"
        publishable-key="pk_live_51Ir6oBJMQ9rLXPTOBjeljRMSdV0bKAZWBYmedJXSXdaku6dvg97NNSHZHAb9egCTsAG3YAmjpneS0w73NJsELjoK00OpmxEF6g"
        customer-email="{{ $user->email }}">
      </stripe-pricing-table>
    </div>
  </div>
</div>

@endsection
