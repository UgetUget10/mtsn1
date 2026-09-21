import { draftMode, cookies } from "next/headers";
import { NextRequest, NextResponse } from "next/server";
import { PREVIEW_TOKEN_COOKIE } from "@/lib/api";

/**
 * Keluar dari Draft Mode. Dipanggil lewat <form method="POST"> pada
 * PreviewBanner (form tidak di-prefetch, jadi cookie tidak terhapus dini).
 */
export async function POST(request: NextRequest) {
  const draft = await draftMode();
  draft.disable();

  const jar = await cookies();
  jar.delete(PREVIEW_TOKEN_COOKIE);

  const back = request.nextUrl.searchParams.get("redirect") || "/";
  // Hanya izinkan path internal — cegah open redirect.
  const target = back.startsWith("/") && !back.startsWith("//") ? back : "/";

  return NextResponse.redirect(new URL(target, request.nextUrl.origin));
}
