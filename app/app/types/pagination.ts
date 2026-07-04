/**
 * Page cursor that travels in a list response's `meta`, alongside `data` (the
 * rows) and `message`. Mirrors the backend's paginator fields
 * (docs/conventions.md API success responses). Domain-agnostic — reused by any
 * paginated seam.
 */
export interface PageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
