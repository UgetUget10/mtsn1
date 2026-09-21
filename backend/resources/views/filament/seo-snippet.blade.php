{{--
    Pratinjau cuplikan mesin pencari — setara "Google preview" Yoast/RankMath.
    Membaca state form secara live lewat Alpine, jadi editor langsung melihat
    bagaimana judul & deskripsi akan tampil di hasil pencarian.

    Variabel yang dikirim komponen:
      $urlBase      — awalan URL publik, mis. "https://mtsn1.test/berita/"
      $titlePath    — path state Livewire untuk judul fallback (mis. "data.title.id")
      $slugPath     — path state slug
      $seoTitlePath, $seoDescPath — path state field SEO
      $fallbackDescPath — path state ringkasan/meta_description sebagai cadangan
--}}
<div
    x-data="{
        get seoTitle() { return ($wire.$get(@js($seoTitlePath)) || '').trim() },
        get rawTitle() {
            const t = $wire.$get(@js($titlePath))
            if (!t) return ''
            return (typeof t === 'object' ? (t.id ?? Object.values(t)[0] ?? '') : t).trim()
        },
        get seoDesc() { return ($wire.$get(@js($seoDescPath)) || '').trim() },
        get rawDesc() {
            const d = $wire.$get(@js($fallbackDescPath))
            if (!d) return ''
            const s = (typeof d === 'object' ? (d.id ?? Object.values(d)[0] ?? '') : d)
            return String(s).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
        },
        get slug() { return ($wire.$get(@js($slugPath)) || '').trim() },

        get title() { return this.seoTitle || this.rawTitle || 'Tanpa judul' },
        get description() {
            const d = this.seoDesc || this.rawDesc
            return d || 'Belum ada deskripsi. Mesin pencari akan mengambil cuplikan dari isi konten.'
        },
        get url() { return @js($urlBase) + (this.slug || '...') },

        /* Batas piksel Google kira-kira setara 60 karakter judul & 155 deskripsi. */
        get titleLen() { return this.title.length },
        get descLen() { return this.description.length },
        get titleState() {
            if (this.titleLen > 60) return { color: 'text-danger-600 dark:text-danger-400', note: 'terlalu panjang, akan terpotong' }
            if (this.titleLen < 25) return { color: 'text-warning-600 dark:text-warning-400', note: 'agak pendek' }
            return { color: 'text-success-600 dark:text-success-400', note: 'panjang ideal' }
        },
        get descState() {
            if (this.descLen > 160) return { color: 'text-danger-600 dark:text-danger-400', note: 'terlalu panjang, akan terpotong' }
            if (this.descLen < 70) return { color: 'text-warning-600 dark:text-warning-400', note: 'agak pendek' }
            return { color: 'text-success-600 dark:text-success-400', note: 'panjang ideal' }
        },
    }"
    class="space-y-3"
>
    {{-- Kartu cuplikan --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <p class="truncate text-xs text-gray-600 dark:text-gray-400" x-text="url"></p>
        <p
            class="mt-0.5 truncate text-lg leading-snug text-[#1a0dab] dark:text-[#8ab4f8]"
            x-text="title"
        ></p>
        <p
            class="mt-1 text-sm leading-relaxed text-gray-700 dark:text-gray-300"
            style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"
            x-text="description"
        ></p>
    </div>

    {{-- Penghitung karakter --}}
    <div class="grid gap-2 sm:grid-cols-2">
        <p class="text-xs">
            <span class="text-gray-500 dark:text-gray-400">Judul:</span>
            <span :class="titleState.color" x-text="titleLen + ' karakter — ' + titleState.note"></span>
        </p>
        <p class="text-xs">
            <span class="text-gray-500 dark:text-gray-400">Deskripsi:</span>
            <span :class="descState.color" x-text="descLen + ' karakter — ' + descState.note"></span>
        </p>
    </div>
</div>
