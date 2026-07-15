# Group 4: JT Express Scaffolding & Client

**Branch:** feature/jt-express-scaffolding
**Status:** pending
**Parent plan:** 2026-07-15-jt-express-driver-plan.md
**Repo:** laraditz/courier-jt-express
**Depends on:** Group 1 (merged)

## Tasks

### Task 4.1 — Package skeleton
- **What:** Create `composer.json` (name `laraditz/courier-jt-express`, PSR-4 `Laraditz\Courier\JtExpress\` → `src/`, `Laraditz\Courier\JtExpress\Tests\` → `tests/`, path repo `../courier`, `laraditz/courier: @dev`, `illuminate/http: ^10.0|^11.0|^12.0|^13.0`, dev deps `orchestra/testbench`/`phpunit/phpunit`), `phpunit.xml`, and `tests/TestCase.php` (Orchestra Testbench bootstrap registering `CourierServiceProvider` + `JtExpressServiceProvider`, seeds `courier.drivers.jtexpress` test config, `fixture()` JSON-loader helper) — mirrors `courier-sfexpress`'s structure exactly
- **Test first:** none — pure scaffolding, no behavior yet
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 4.2 — config/jtexpress.php
- **What:** Create config file with `api_account`, `private_key`, `customer_code`, `password`, `sandbox`, `base_url` (`https://ylopenapi.jtexpress.my/webopenplatformapi/api`), `sandbox_url` (`https://demoopenapi.jtexpress.my/webopenplatformapi/api`), `timeout` (30) keys, `env()`-backed
- **Test first:** none — static config array
- **Agent:** iris
- **Subagent:** no
- **Est:** 2 min

### Task 4.3 — JtExpressServiceProvider
- **What:** `register()` merges `config/jtexpress.php` into `courier.drivers.jtexpress`; `boot()` extends the `courier` manager with `'jtexpress'`, publishes config — mirrors `SfExpressServiceProvider`
- **Test first:** a minimal test confirming `app('courier')->driver('jtexpress')` resolves to a `JtExpressDriver` instance (a temporary driver stub is fine here — Task 5.2 fleshes out the real class)
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 4.4 — JtExpressSigner
- **What:** `digest(string $bizContentJson): string` → `base64_encode(md5($json . $privateKey, true))`; `hashPassword(string $plaintext): string` → `strtoupper(md5($plaintext))`
- **Test first:** `tests/Http/JtExpressSignerTest.php` — assert digest/hash against hand-computed expected values for known inputs
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 4.5 — JtExpressClient::dispatch() happy path
- **What:** `dispatch(string $path, array $bizContent): array` — signs the request via `JtExpressSigner`, POSTs `application/x-www-form-urlencoded` with `apiAccount`/`digest`/`timestamp` headers and a `bizContent` form field, returns the decoded envelope on `code === '1'`
- **Test first:** `tests/Http/JtExpressClientTest.php` — `Http::fake()`, assert correct headers/body sent and envelope returned
- **Agent:** iris
- **Subagent:** no
- **Est:** 5 min

### Task 4.6 — JtExpressClient error handling
- **What:** Throw `CourierException` on HTTP failure or business-level `code !== '1'`
- **Test first:** extend `tests/Http/JtExpressClientTest.php` with an HTTP-failure case and a business-error case
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min
