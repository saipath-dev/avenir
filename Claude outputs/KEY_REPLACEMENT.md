# Replacing the AI keys on the development server (tf-intake-r1, 26 Oct 2026)

Confirmed by the system's own check (Tour Inquiries → "Check the reader"):
- Anthropic key (ANTHROPIC_API_KEY): HTTP 401 authentication_error "invalid x-api-key". Fingerprint 1a520362.
  Same key worked on 12 Oct 2026 19:12 (INQ 1024 read by claude-sonnet-5). Unchanged in every backup since 21 Sep.
- OpenAI key (OPENAI_API_KEY): HTTP 401 invalid_request_error "Incorrect API key provided". Fingerprint 59b63391.
  Rejected since at least 11 Oct 2026 (ItineraryFormatter log).

Steps (account owner / Nethanjana) — never paste a key into chat, e-mail, screenshots or tickets:
1. Anthropic: console.anthropic.com → API Keys. If the old key is missing, disabled, or the organisation shows
   "no credit / billing issue", that is the cause. Create a new key (name it e.g. avenir-dev-2026-10). Copy it once.
2. On the development PC open C:\wamp64\www\avenir\.env in Notepad. Replace only the value after
   ANTHROPIC_API_KEY = with the new key. Save. Do not change any other line.
3. OpenAI (only if the Itinerary Formatter is still wanted): platform.openai.com → API keys → create key, put it
   after OPENAI_API_KEY = in the same file. Otherwise leave as is and tell Shantha the formatter stays off.
4. No restart is needed. Open http://localhost/avenir/tour/intake and press "Check the reader":
   the fingerprint must change and the pill must say "working" (HTTP 200).
5. Open INQ 1030 (http://localhost/avenir/tour/intake?id=30) and press "Try reading it again".
6. Do not copy the new key to live/UAT or anywhere else without Shantha's separate approval.
7. Revoke the old keys in both consoles once the new ones work.
