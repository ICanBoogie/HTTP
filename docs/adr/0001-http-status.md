# ADR 0001: Represent HTTP status with a single immutable `Status` class

- **Status:** Accepted
- **Date:** 2026-10-11
- **Target version:** 7.0.0 (major, backward compatibility may be broken)

## Context

HTTP status handling is spread over two types that overlap:

| Piece                        | Role                                                    | Problem                                                                                                                                                                              |
|------------------------------|---------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `ResponseStatus` (interface) | ~60 `STATUS_*` int constants, implemented by `Response` | An interface used as a constant bag. Constants leak into every `Response` subclass and are reached as `$response::STATUS_OK`, `ResponseStatus::STATUS_OK`, or `Response::STATUS_OK`. |
| `Status` (class)             | Mutable value object: `code`, `message`, `is_*` hooks   | Mutable (cloned by `Response`, again by `FinalResponse`). Carries ~50 deprecated duplicate constants (`Status::NOT_FOUND`) and a partial reason-phrase table.                        |
| `StatusCodeNotValid`         | Exception for out-of-range code                         | Check is duplicated (`assert_code_is_valid`, `is_valid`).                                                                                                                            |
| `Response::$status`          | `Status` with an `int\|Status` setter                   | Two types in, one out. `Response::__construct` and `RedirectResponse` take a bare `int`.                                                                                             |

Concrete defects:

1. **Incomplete reason table.** `new Status(ResponseStatus::STATUS_TOO_MANY_REQUESTS)` (429), `308`,
   `422`, `207`, … pass the 100–599 check, then `self::CODES_AND_MESSAGES[$code]` raises an
   undefined-key warning.
2. **Dangling or misspelled constants.** `Status::USE_PROXY` points to the nonexistent
   `ResponseStatus::STATUS_USE_PROXY`. `STATUS_RECONDITION_REQUIRED` is a typo for
   `PRECONDITION_REQUIRED` (428).
3. **`is_empty` is wrong.** It includes 201. The RFC 9110 "no body" set is 1xx, 204, 304, and
   `Response::finalize()` hardcodes it separately (two sources of truth).
4. **`is_cacheable` is not RFC-based.** `STATUS_NOT_FOUND` is listed twice, and 204, 206, 308, 405,
   414 and 501 are missing (RFC 9110 §15.1).
5. **`is_valid` is dead.** The constructor and the `code` setter already throw, so it is always
   `true`.
6. **Mutability.** `code` and `message` are settable, hence the `clone` calls.
7. **`message` hook.** The getter re-derives the default on every read while the constructor also
   stores it; `->message = null` "resets" by accident.
8. **`Status::from()` accepts four shapes** (`int`, `"200 OK"`, `[200, "OK"]`, `Status`). Only `int`
   and `Status` are used by the library.
9. **Three ways to name a code**: `ResponseStatus::STATUS_OK`, deprecated `Status::OK`,
   `new Status(200)`.

Custom codes and reason phrases (`499`, `520`–`527`, `new Status(200, "Madonna")`) are supported
today and must remain so.

## Decision drivers

- Keep the property-style API (`$status->code`, `$status->is_redirect`).
- Support non-standard codes and phrases.
- One source of truth for constants, phrases, and body/cacheability rules.
- Immutability: a status should not need cloning.
- A major version allows breaking changes.

## Considered options

### Option A: Backed enum `HttpStatus` + `CustomStatus`, shared `Status` interface (Spring 6 model)

An enum for standard codes, a final class for the rest, both implementing `Status`. Gives an
exhaustive `match` for phrases, immutable singleton cases, and `HttpStatus::NotFound`.

**Rejected.** PHP enums cannot declare properties, hooked or not (tested on PHP 8.5:
`Enum E cannot include properties`), so an enum cannot implement an interface declaring
`public int $code { get; }`. The interface would have to use methods (`$status->code()`,
`$status->is_redirect()`), breaking the property API the library exposes today.

### Option B: Enum catalog + `Status` value object

`HttpStatus` enum for names and phrases, `Status` wrapping it for the API. **Rejected**: two types
for one concept and a double wrapping, for little gain over option C.

### Option C: One immutable `Status` class with `int` constants (Rust / Ktor model) — **chosen**

Other ecosystems for reference: Python `IntEnum` (no custom codes), .NET open `enum` (not possible
in PHP), Spring 6 interface + enum + class (option A), Vapor enum with a `.custom` case, Rust and
Ktor value type with named constants, Symfony / Laravel / PSR-7 `int` constants plus a phrase map.

## Decision

Replace `ResponseStatus` and the current `Status` with one `final` immutable `Status` class.

```php
final class Status
{
    private const int MIN = 100;     // private: keeps the public constants to actual status codes
    private const int MAX = 599;

    // Complete, un-prefixed; replaces ResponseStatus::STATUS_* and the deprecated Status::* duplicates.
    public const int CONTINUE = 100;
    public const int OK = 200;
    public const int NOT_FOUND = 404;
    public const int IM_A_TEAPOT = 418;
    public const int TOO_MANY_REQUESTS = 429;
    // …

    private const array REASONS = [ /* every constant above, no gaps */ ];

    // The only way to get a Status. `$reason` overrides the standard phrase and is never memoized.
    public static function from(int|self $status, ?string $reason = null): self;

    // Thin helpers for the most used codes only (see rule 6): `return self::from(self::OK);`
    public static function of_ok(): self;
    public static function of_created(): self;
    public static function of_not_found(): self;
    // …

    public readonly string $reason;              // always a string once constructed

    private function __construct(
        public readonly int $code,
        ?string $reason,                         // null → standard reason for the code, '' if unknown
    );                                           // throws StatusCodeNotValid outside MIN..MAX

    // Get-only virtual hooks: no backing value, so the class stays effectively immutable.
    public bool $is_informational { get => … }
    public bool $is_successful { get => … }
    public bool $is_redirect { get => … }
    public bool $is_client_error { get => … }
    public bool $is_server_error { get => … }
    public bool $is_error { get => … }
    public bool $is_ok { get => … }
    public bool $is_forbidden { get => … }
    public bool $is_not_found { get => … }
    public bool $has_no_body { get => … }                  // 1xx, 204, 304
    public bool $is_heuristically_cacheable { get => … }   // RFC 9110 §15.1

    public function __toString(): string;                  // "404 Not Found"
}
```

### Rules

1. **Plain `final class`, not `readonly class`.** A readonly class cannot contain hooked properties
   (`Hooked properties cannot be readonly`). `code` is a `public readonly` promoted property and
   `reason` a `public readonly string` assigned in the constructor; predicates are get-only virtual hooks. Nothing is writable after construction.
2. **Custom codes and phrases need no extra type**: `Status::from(499, 'Client Closed Request')`,
   `Status::from(200, 'Madonna')`. Predicates are range checks, so `499` is still a client error.
   The constructor is `private` so every instance goes through `from()` (and its memoization).
   With a `Status` as first argument and a non-null `$reason`, `from()` returns a new instance with
   that code and reason.
3. **Validation lives in the (private) constructor**, throwing `StatusCodeNotValid` outside `MIN..MAX`
   (100–599). With custom statuses allowed, it is the only guard against `Status::from(0)`, a PHP exit
   code, an exception code like `5000`, or swapped arguments. The predicates assume the five RFC
   9110 classes; Symfony, PSR-7 implementations and `http_response_code()` use the same bounds;
   vendor codes (`499`, `520`–`527`, `599`) fit. Go and Rust accept up to 999, a strictness choice
   rather than an RFC requirement. `MIN`/`MAX` are private so the public constants are only
   actual status codes (a public `Status::MAX` sitting next to `Status::OK` is confusing); the range
   is documented here and easy to relax. A `Status` is therefore always valid, and `is_valid` is removed.
4. **Equality is by value, identity is not part of the contract.** There is no `equals()` method:
   `==` compares the properties, as with `DateTime`, and nobody checks `===` there either.
   `Status::from(404) == Status::of_not_found()` is true (the default reason is resolved at
   construction), while `Status::from(200, 'Madonna') != Status::of_ok()` because the reason differs.
   To compare codes regardless of the reason, use `->code`. `from()` memoizes one instance per code
   (a private static cache, which the `of_*` helpers go through) to avoid allocations, but this is an
   optimization: callers must not rely on `===`. Only instances without a custom reason are cached
   (a custom reason equal to the standard one counts as none), so the cache is bounded by the number
   of valid codes (500) instead of growing with arbitrary phrases.
5. **Constants stay `int`** so they work in `match`, array keys, constant expressions, and parameter
   defaults (`int $code = Status::NOT_FOUND`). Reserved words are legal constant and method names
   (checked on PHP 8.5), so `CONTINUE_` becomes `CONTINUE`.
6. **Named helpers use the `of_` prefix** (`Status::of_not_found()`). It reads naturally, groups the
   helpers in autocomplete, and cannot collide with instance members or future additions. `from_` is
   rejected (it already means conversion: `from(int|Status)`), `for_` reads oddly. Helpers are thin
   one-liners through `from()`; no `__callStatic`, which would lose autocomplete and static
   analysis. A static call cannot be a parameter default, which is why the constants are kept
   alongside. **Helpers cover only the codes used in everyday application logic**, not the whole
   table. Every other code stays reachable through its constant: `Status::from(Status::GONE)`.
   Initial set (22):
    - Success: `of_ok` (200), `of_created` (201), `of_accepted` (202), `of_no_content` (204)
    - Redirects: `of_moved_permanently` (301), `of_found` (302), `of_see_other` (303),
      `of_not_modified` (304), `of_temporary_redirect` (307), `of_permanent_redirect` (308)
    - Client errors: `of_bad_request` (400), `of_unauthorized` (401), `of_forbidden` (403),
      `of_not_found` (404), `of_method_not_allowed` (405), `of_conflict` (409),
      `of_unprocessable_entity` (422), `of_too_many_requests` (429)
    - Server errors: `of_internal_server_error` (500), `of_bad_gateway` (502),
      `of_service_unavailable` (503), `of_gateway_timeout` (504)

   Adding a helper later is not a BC break, so the set can grow on demand.
7. **`reason` is stored, not re-derived per read.** The private constructor takes `?string $reason` and
   assigns `$reason ?? self::REASONS[$code] ?? ''`, so the property is never null. `null`
   means "use the standard phrase", while an explicit `''` is kept as given (no phrase). The
   constructor parameter is not promoted because its type (`?string`) differs from the property's
   (`string`). `message` is renamed `reason` (RFC 9110 term, PSR-7
   `getReasonPhrase`).
8. **Single source of truth.**
    - `Response::finalize()` asks `$status->has_no_body` instead of hardcoding 1xx/204/304.
    - `Response::$is_cacheable` delegates to `$status->is_heuristically_cacheable` (200, 203, 204,
      206, 300, 301, 308, 404, 405, 410, 414, 501).
    - `RedirectResponse` keeps its `is_redirect` check and defaults to `Status::FOUND`.
    - Exceptions (`NotFound`, `ClientError`, …) default their `$code` to `Status::*` constants.

### Removed

- `ResponseStatus`, and `implements ResponseStatus` on `Response`, with no deprecated alias period.
- The `code` and `message` setters, `is_valid`, and the `clone` of `Status` in `Response::__clone()`
  and `finalize()`.
- The public constructor (`new Status(...)`) and the polymorphic `Status::from(array|int|string|Status)`;
  `from(int|Status, ?string $reason = null)` remains. Parsing
  a raw status line (`"200 OK"`) is no longer supported.
- The deprecated `Status::*` duplicates (the names live on as the new, complete, non-deprecated
  constants), except `Status::USE_PROXY` (305), which is dropped for good: RFC 7231 deprecated the
  code and the reference to `ResponseStatus::STATUS_USE_PROXY` was already dangling.

### Renames

| Old                                                                | New                                   |
|--------------------------------------------------------------------|---------------------------------------|
| `ResponseStatus::STATUS_NOT_FOUND`, deprecated `Status::NOT_FOUND` | `Status::NOT_FOUND`                   |
| `STATUS_RECONDITION_REQUIRED`                                      | `Status::PRECONDITION_REQUIRED`       |
| `STATUS_UPDATE_REQUIRED`, `STATUS_I_M_A_TEAPOT`                    | `Status::UPGRADE_REQUIRED`, `Status::IM_A_TEAPOT` |
| `$status->message`                                                 | `$status->reason`                     |
| `$status->is_empty`                                                | `$status->has_no_body`                |
| `$status->is_cacheable`                                            | `$status->is_heuristically_cacheable` |
| `$status->is_valid`                                                | removed (constructor validates)       |
| `Status::from("200 OK")`, `Status::from([200, "OK"])`              | removed; use `Status::from(200, "OK")` |
| `new Status(404)`, `new Status(499, "…")`                          | `Status::from(404)`, `Status::from(499, "…")` |

`$status->code`, the range predicates (`is_redirect`, `is_client_error`, `is_server_error`, …), and
`is_ok`, `is_forbidden`, `is_not_found` keep their names and property syntax. The last three are
exact-code checks kept for readability.

## Consequences

**Positive**

- One type and one set of constants; the phrase table, body rule, and cacheability rule each live in
  one place.
- Phrase-table warnings, dangling and misspelled constants, and wrong predicates are fixed.
- Immutable: no `clone` in `Response` or `FinalResponse`.
- Property API and custom codes/phrases are preserved.
- `Status::of_*()` gives enum-like ergonomics (`new Response($body, Status::of_created())`) while
  `int` still works.

**Negative**

- BC breaks: `ResponseStatus` removed, `message` → `reason`, `is_empty` / `is_cacheable` renamed,
  `Status` immutable with a private constructor, `from()` narrowed. All require `CHANGELOG.md` entries.
- 22 near-identical `of_*` helpers. Codes without a helper are reached through `Status::from(Status::GONE)`,
  a slightly less pleasant spelling for rare codes.
- No exhaustive `match` over standard codes: a new constant without a phrase is caught by a test,
  not statically.
- `==` includes the reason, so a status with a custom phrase is not loosely equal to the standard
  one; comparing by `->code` is the way to ignore the phrase.
- The memoization cache is shared static state, kept private and append-only, so safe in practice.

## Open questions

None.

## Resolved questions

- `USE_PROXY` (305, deprecated by RFC 7231) is removed, along with its entry in the reason table.
- `is_ok`, `is_forbidden` and `is_not_found` are kept.
- `ResponseStatus` is dropped outright, with no deprecated alias period.
- `from()` memoizes instances as an optimization; identity (`===`) is not part of the contract.
- `Status::from_line()` is not added; raw status line parsing is dropped.

## Implementation plan

1. **Fix the defects** in the existing class (can ship alone): complete the table, fix the typo and
   remove `USE_PROXY`, fix the two predicates, remove `is_valid`.
2. **Make `Status` final and immutable**: `public readonly` `code`/`reason`, remove setters, drop
   the `clone` calls in `Response` and `FinalResponse`, rename `message` to `reason`, make the
   constructor private and replace `new Status(...)` call sites with `Status::from(...)`.
3. **Move constants**: complete the `Status::*` list, un-deprecate it, switch internal usages
   (`lib/` and `tests/`, about 20 files reference `ResponseStatus`), add the `of_*` helpers.
4. **Remove `ResponseStatus`**, the polymorphic `from()`, and `implements ResponseStatus`.
5. **Docs**: update `docs/Response.md` and `docs/Responders.md`; add `CHANGELOG.md` entries under
   Backward Incompatible Changes.

## Tests

- Every public `Status::*` constant (reflection, `IS_PUBLIC`; `MIN`, `MAX` and `REASONS` are private)
  has a non-empty reason.
- Each of the 22 `of_*` helpers returns the status with the expected code (data provider mapping
  helper name to constant).
- Predicate boundaries for 99, 100, 199, 200, 299, 300, 399, 400, 499, 500, 599, 600.
- `Status::from(499)` is a client error with an empty reason and no warning; 0, 99 and 600 throw
  `StatusCodeNotValid`.
- `Status::from(200, "Madonna")` prints `200 Madonna`, and `Status::from(200, "Madonna") != Status::of_ok()`.
- `Status::from(200, "OK") == Status::of_ok()`, and `Status::from(200, "")` keeps its empty reason.
- `new Status(...)` is not callable from outside (private constructor).
- `has_no_body` matches what `Response::finalize()` strips (1xx, 204, 304).
- Assigning to `$status->code` or `$status->reason` throws an `Error`.
- `Status::from(404) == Status::of_not_found()`.
