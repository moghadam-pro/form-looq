# Remote content contract

The Add-ons and Help screens can load their content from the project site. This
document is the contract those endpoints must satisfy. Upload the two files
described here to `sayid.ir` and the plugin will pick them up; until then, every
screen falls back to a catalogue bundled in the plugin.

## Endpoints

| Screen | Human page | JSON endpoint |
| --- | --- | --- |
| Add-ons | `https://sayid.ir/mpro-forms/addons` | `https://sayid.ir/mpro-forms/addons.json` |
| Help | `https://sayid.ir/mpro-forms/docs` | `https://sayid.ir/mpro-forms/docs.json` |

Both must be served over HTTPS with `Content-Type: application/json; charset=utf-8`
and HTTP 200. Any other status is treated as unavailable.

## Request behaviour

- Method `GET`, `Accept: application/json`, 8-second timeout.
- User agent: `MPROForms/<version>; <site home URL>`. This is the only
  site-identifying data sent, and it exists so the project can tell which
  releases are in use. No form content, entry data, or personal data is
  transmitted.
- Successful responses are cached in a transient for the number of hours set
  under Settings → Add-ons (default 12, range 1–168).
- Failures cache an empty result for at most one hour, so a broken endpoint does
  not slow down every admin page load.
- The whole mechanism is disabled when "Fetch the add-on list" is unticked, after
  which the plugin makes no external requests at all.

## `addons.json`

```json
{
  "version": 1,
  "addons": [
    {
      "slug": "polls",
      "name": "رای‌گیری",
      "description": "نظرسنجی تک‌سؤالی با نمایش زندهٔ نتایج.",
      "icon": "chart-bar",
      "status": "planned",
      "url": "https://sayid.ir/mpro-forms/addons/polls",
      "badge": "به‌زودی"
    }
  ]
}
```

| Field | Required | Rules |
| --- | --- | --- |
| `slug` | yes | Lowercase key. Sanitised with `sanitize_key()`; an entry without one is dropped. |
| `name` | yes | Plain text. An entry without one is dropped. |
| `description` | no | Plain text, single line. |
| `icon` | no | A [Dashicon](https://developer.wordpress.org/resource/dashicons/) name without the `dashicons-` prefix. Defaults to `admin-plugins`. |
| `status` | no | `available` or `planned`. Anything else becomes `planned`. |
| `url` | no | Absolute HTTPS URL to the add-on's page. Rendered as a "Details" link. |
| `badge` | no | Short label shown in the card corner. |

At most 60 entries are read. Every value is sanitised server side before it
reaches the page, so a compromised endpoint cannot inject markup.

## `docs.json`

```json
{
  "version": 1,
  "sections": [
    {
      "title": "شروع کار",
      "description": "اولین فرم را بسازید و روی یک برگه بگذارید.",
      "url": "https://sayid.ir/mpro-forms/docs/getting-started",
      "articles": [
        {
          "title": "ساخت یک فرم",
          "url": "https://sayid.ir/mpro-forms/docs/creating-a-form",
          "excerpt": "یک تمپلیت انتخاب کنید، نام فرم را بگذارید و فیلدها را بچینید."
        }
      ]
    }
  ]
}
```

| Field | Required | Rules |
| --- | --- | --- |
| `sections[].title` | yes | Plain text. A section without one is dropped. |
| `sections[].description` | no | Plain text, shown under the heading. |
| `sections[].url` | no | Absolute HTTPS URL. |
| `sections[].articles[].title` | yes | Plain text. An article without one is dropped. |
| `sections[].articles[].url` | no | Absolute HTTPS URL. Without it the title renders as plain text. |
| `sections[].articles[].excerpt` | no | One-line summary. |

At most 30 sections and 30 articles per section are read.

## Localisation

The plugin does not translate remote content — it renders whatever the endpoint
returns. Serve the language you want administrators to see, or key the response
off the `Accept-Language` header if you decide to localise later. Note that the
cache is not keyed by language, so if you add per-language responses you should
also vary on that header.

## Versioning

The `version` field is currently informational; the plugin reads the arrays
directly. If a breaking change becomes necessary, publish the new shape at
`addons-v2.json` and leave the v1 file in place, because older plugin versions
will keep requesting it indefinitely.

## Testing an endpoint

```bash
curl -sS -H 'Accept: application/json' https://sayid.ir/mpro-forms/addons.json | head -40
```

Inside WordPress, flush the cache to force a refetch:

```php
\MPROForms\Remote_Content::flush();
```

When the plugin is showing bundled content, the Add-ons screen says so
explicitly, which is the fastest way to tell whether an endpoint is reachable
from a given site.
