/**
 * Authenticated account summary returned by the UC-00 API seam
 * (`POST /api/login` and `GET /api/user`). Shape mirrors the `user` object the
 * backend serialises from `user_accounts`.
 */
export interface AuthUser {
  id: number;
  display_name: string;
  email: string;
  role: "citizen" | "staff_member" | "administrator";
  account_state: "active" | "inactive";
}

/**
 * One success envelope for every seam: `data` carries the payload, `message` a
 * short summary. Errors omit `data` (`{ message, errors? }`)
 * [docs/conventions.md API success responses].
 */
interface LoginResponse {
  data: {
    token: string;
    token_type: string;
    user: AuthUser;
  };
  message: string;
}

interface MeResponse {
  data: AuthUser;
  message: string;
}

/**
 * Sign-in state for UC-00. Holds the Sanctum bearer token and the current
 * account, and is the single caller of the auth API seam:
 *   - `POST /api/login`  (public)          → { data: { token, token_type, user }, message }
 *   - `GET  /api/user`   (auth:sanctum)    → { data: user, message }
 * The token is kept in a cookie so it survives reloads (SSR-safe).
 */
export const useAuthStore = defineStore("auth", () => {
  const token = useCookie<string | null>("pf_token", { sameSite: "lax" });
  const user = ref<AuthUser | null>(null);

  const isAuthenticated = computed(() => Boolean(token.value));

  function apiBase(): string {
    return useRuntimeConfig().public.apiBase;
  }

  /** Exchange credentials for a bearer token; throws on 401/422 for the caller to render. */
  async function login(email: string, password: string): Promise<void> {
    const res = await $fetch<LoginResponse>("/login", {
      baseURL: apiBase(),
      method: "POST",
      headers: { Accept: "application/json" },
      body: { email, password },
    });

    token.value = res.data.token;
    user.value = res.data.user;
  }

  /** Load the current account for the stored token; used to show role-based functions. */
  async function fetchUser(): Promise<void> {
    if (!token.value) {
      return;
    }

    const res = await $fetch<MeResponse>("/user", {
      baseURL: apiBase(),
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token.value}`,
      },
    });

    user.value = res.data;
  }

  /** Drop the local session (client-side sign-out). */
  function logout(): void {
    token.value = null;
    user.value = null;
  }

  return { token, user, isAuthenticated, login, fetchUser, logout };
});
