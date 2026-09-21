"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { unlockPost, unlockCookieName } from "@/lib/post-unlock";

/**
 * Form kata sandi untuk post terproteksi (wp: the_password_form). Setelah kata
 * sandi benar, simpan token unlock di cookie lalu refresh — Server Component
 * getPost() akan membaca cookie dan menampilkan isi penuh.
 */
export function PostPasswordForm({ slug }: { slug: string }) {
  const router = useRouter();
  const [status, setStatus] = useState<"idle" | "checking" | "error">("idle");
  const [message, setMessage] = useState("");

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const password = new FormData(e.currentTarget).get("password") as string;
    setStatus("checking");
    try {
      const { token, expires_in } = await unlockPost(slug, password);
      document.cookie = `${unlockCookieName(slug)}=${encodeURIComponent(token)}; path=/; max-age=${expires_in}; samesite=lax`;
      router.refresh();
    } catch (err) {
      setStatus("error");
      setMessage(err instanceof Error ? err.message : "Terjadi kesalahan.");
    }
  }

  return (
    <div className="mx-auto max-w-md rounded-2xl border border-border bg-surface-muted p-6 text-center sm:p-8">
      <span className="mx-auto grid h-12 w-12 place-items-center rounded-full bg-brand-light text-brand-dark">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" />
          <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
      </span>
      <h2 className="mt-4 text-lg font-bold text-ink">Artikel terlindungi</h2>
      <p className="mt-1 text-sm text-ink-muted">
        Masukkan kata sandi untuk membaca isi artikel ini.
      </p>
      <form onSubmit={onSubmit} className="mt-5 space-y-3">
        <input
          name="password"
          type="password"
          required
          autoComplete="off"
          placeholder="Kata sandi"
          className="field text-center"
        />
        <button
          type="submit"
          disabled={status === "checking"}
          className="btn-glow inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand px-6 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark disabled:opacity-60"
        >
          {status === "checking" ? "Memeriksa…" : "Buka artikel"}
        </button>
        {status === "error" && (
          <p role="alert" className="rounded-xl border border-danger/30 bg-danger-soft px-4 py-2 text-sm font-medium text-danger">
            {message}
          </p>
        )}
      </form>
    </div>
  );
}
