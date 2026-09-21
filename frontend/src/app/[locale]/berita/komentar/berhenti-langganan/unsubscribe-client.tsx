"use client";

import { useState } from "react";
import Link from "next/link";

const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

export function UnsubscribeClient({ token }: { token: string }) {
  const [state, setState] = useState<"idle" | "loading" | "done" | "error">(
    "idle",
  );
  const [message, setMessage] = useState("");

  async function unsubscribe() {
    if (!token) return;
    setState("loading");
    try {
      const res = await fetch(
        `${BASE}/comments/unsubscribe/${encodeURIComponent(token)}`,
        { headers: { Accept: "application/json" } },
      );
      const body = (await res.json().catch(() => ({}))) as { message?: string };
      if (!res.ok) throw new Error(body.message ?? "Gagal memproses permintaan.");
      setState("done");
      setMessage(
        body.message ??
          "Anda tidak akan lagi menerima email balasan untuk komentar ini.",
      );
    } catch (err) {
      setState("error");
      setMessage(
        err instanceof Error ? err.message : "Terjadi kesalahan. Coba lagi.",
      );
    }
  }

  if (!token) {
    return (
      <p className="rounded-xl border border-danger/30 bg-danger-soft px-4 py-3 text-sm font-medium text-danger">
        Tautan tidak valid. Buka tautan berhenti-langganan dari email yang Anda
        terima.
      </p>
    );
  }

  if (state === "done") {
    return (
      <div className="space-y-4">
        <p className="rounded-xl border border-success/25 bg-success-soft px-4 py-3 text-sm font-medium text-success">
          {message}
        </p>
        <Link
          href="/berita"
          className="text-sm font-semibold text-brand-dark hover:underline"
        >
          ← Kembali ke berita
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <p className="text-sm text-ink-soft">
        Klik tombol di bawah untuk berhenti menerima email pemberitahuan saat
        komentar Anda mendapat balasan.
      </p>
      <button
        type="button"
        onClick={unsubscribe}
        disabled={state === "loading"}
        className="btn-glow inline-flex min-h-12 items-center justify-center rounded-xl bg-brand px-7 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
      >
        {state === "loading" ? "Memproses…" : "Ya, berhenti langganan"}
      </button>
      {state === "error" && (
        <p
          role="alert"
          className="rounded-xl border border-danger/30 bg-danger-soft px-4 py-3 text-sm font-medium text-danger"
        >
          {message}
        </p>
      )}
    </div>
  );
}
