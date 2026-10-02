# WORKFLOW & STANDAR PRODUKSI VIDEO REMOTION SISTEM SALE
> Panduan arsitektur teknis, standar tipografi, sinkronisasi audio tingkat kata, dan SOP produksi untuk seluruh video panduan sistem SALE (Video 00 s.d. Video 20).

---

## 1. IKHTISAR & PRINSIP UTAMA

Setiap video panduan SALE dibangun menggunakan **Remotion (React + TypeScript + Tailwind CSS)** dengan standar visual profesional, elegan, berwibawa, dan bebas dari ciri "AI-Slop".

### Prinsip Desain Kunci:
1. **Presisi Audio (Voice-Synchronized)**: Setiap animasi kata kunci, kemunculan poin, dan pergantian fase layar **wajib selaras tepat pada milidetik suara Voice Over (VO)**.
2. **Hierarki Tipografi Tajam**: Heading wajib tebal (`font-extrabold`), subjudul wajib ringan (`font-normal`), dan poin kunci berbobot sedang (`font-semibold`). Dilarang heading dan deskripsi sama-sama tebal.
3. **Poin Ringkas & Vokal**: Hindari paragraf esai atau kalimat penjelas 2 baris di bawah setiap poin. Cukup tampilkan frasa kunci yang padat dan langsung pada intinya.
4. **List Bernomor Menggunakan Badge**: Angka urut (01, 02, 03, dst.) wajib menggunakan container badge ber-background lembut dan ber-border halus, bukan angka polos tanpa pembungkus.
5. **Cover & Penutup 3 Baris Bersih**: Tidak menggunakan kotak card pembungkus. Tepat 3 baris terpusat: Baris 1 Judul Utama, Baris 2 Subjudul, Baris 3 Nama Institusi.

---

## 2. PIPELINE PRODUKSI STEP-BY-STEP

### Langkah 1: Penyiapan Aset Voice Over
- Letakkan berkas audio VO resmi pada folder: `remotion/public/vo/voX.mp3` (misal: `vo0.mp3`, `vo1.mp3`, dst.).
- Catat durasi total audio dalam detik.
- Hitung total frame video pada 30 FPS: $\text{Total Frame} = \text{Durasi Detik} \times 30$.

### Langkah 2: Ekstraksi Timestamp Tingkat Kata (Faster-Whisper)
Jalankan skrip Python ekstraksi kata demi kata untuk mendapatkan posisi milidetik yang presisi:
```python
import json
from faster_whisper import WhisperModel

model = WhisperModel("base", device="cpu", compute_type="int8")
segments, info = model.transcribe("remotion/public/vo/vo1.mp3", word_timestamps=True, language="id")

results = []
for segment in segments:
    words = [{"word": w.word, "start": round(w.start, 2), "end": round(w.end, 2)} for w in segment.words]
    results.append({
        "start": round(segment.start, 2),
        "end": round(segment.end, 2),
        "text": segment.text,
        "words": words
    })

with open("src/videos/video_XX/voX_timestamps.json", "w", encoding="utf-8") as f:
    json.dump(results, f, indent=2, ensure_ascii=False)
```
File JSON yang dihasilkan menjadi **Single Source of Truth** untuk seluruh penentuan frame animasi.

### Langkah 3: Partisi Adegan (Scene Splitting) & Durasi
Petakan segmen VO ke dalam adegan-adegan logis. Di file master video (`VideoXX.tsx`):
```tsx
export const SCENE_DURATIONS = {
  scene1: 912,  // Intro & Visi (VO 0.0s - 30.2s)
  scene2: 1138, // 4 Peran Civitas (VO 30.2s - 67.7s)
  scene3: 1103, // Kerangka OBE (VO 67.7s - 104.1s)
  scene4: 756,  // Fitur Cerdas (VO 104.1s - 128.9s)
  scene5: 669,  // Outro & Panduan (VO 128.9s - 150.56s)
};

export const TRANSITION_DURATION = 12; // 0.4 detik fade/slide di celah jeda jeda sunyi VO
```

### Langkah 4: Pemetaan Frame Lokal di Dalam Scene
Hitung posisi awal lokal frame setiap fase di dalam Scene:
$$\text{Local Frame} = (\text{Start Audio Detik} \times 30) - \text{Global Start Frame Scene}$$

Contoh Sinkronisasi Fase di dalam Scene Component:
```tsx
// Seg 04 (0–205f): Overview Alur Kerja
// Seg 05 (205–360f): Admin Sistem
// Seg 06 (360–550f): Admin Prodi
// Seg 07 (550–765f): Dosen Pengampu
// Seg 08–09 (765–1138f): Mahasiswa
const isOverview = frame < 205;
const isAdminSistem = frame >= 205 && frame < 360;
const isAdminProdi = frame >= 360 && frame < 550;
const isDosen = frame >= 550 && frame < 765;
const isMahasiswa = frame >= 765;
```

---

## 3. STANDAR TIPOGRAFI & TATA LETAK

| Elemen | Ukuran Font | Font Weight | Warna Dark Navy (`#102f50`) | Warna Light Cream (`#F5F3EE`) |
|---|---|---|---|---|
| **Label Kategori** | `text-xs` (12px) | `font-bold` (700) | `text-slate-400` + `tracking-[0.25em] uppercase` | `text-[#102f50]/70` + `tracking-[0.25em] uppercase` |
| **Judul Utama (Heading)** | `text-[52px]`–`[60px]` | `font-extrabold` (800) | `text-white` (`#ffffff`) | `text-[#0f172a]` (Dark Slate) |
| **Subjudul** | `text-2xl`–`3xl` (24–30px) | `font-normal` (400) | `text-slate-300` | `text-slate-600` |
| **Poin Kunci (List Item)** | `text-2xl` (24px) | `font-semibold` (600) | `text-slate-100` | `text-slate-800` |
| **Nomor Badge** | `text-lg` (18px) | `font-bold` (700) | `text-white` | `text-[#102f50]` |

> [!IMPORTANT]
> **Larangan Benturan Warna**: Pada latar belakang biru tua (`#102f50`), **DILARANG** menggunakan teks warna cyan/sky blue (`sky-300`, `sky-400`) karena terlihat menabrak dan amatir. Gunakan Pure White (`#ffffff`) dan Light Slate (`#cbd5e1`).

---

## 4. SPESIFIKASI KOMPONEN RESMI

### 1. Badge Nomor List (01, 02, 03, dst.)
Gunakan container badge kotak rounded dengan latar belakang semi-transparan:
- **Tema Gelap (Dark Navy):**
  ```tsx
  <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
    01
  </span>
  ```
- **Tema Terang (Light Cream):**
  ```tsx
  <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-[#102f50]/10 border border-[#102f50]/15 text-[#102f50] font-bold text-lg shadow-sm shrink-0">
    01
  </span>
  ```

### 2. Animasi Kata per Kata (`WordByWord.tsx`)
Komponen animasi teks sinkron suara dengan *spring punch* pada kata yang disorot:
```tsx
<WordByWord
  text="Melampaui Presensi dan Angka"
  startFrame={6}
  durationInFrames={40}
  theme="dark" // "dark" atau "light"
  highlightWords={["Presensi", "Angka"]}
/>
```

### 3. Bingkai Screenshot Antarmuka (`ShowcaseDisplay.tsx`)
Preview layar sistem SALE 16:9 dengan bingkai tebal berwibawa:
```tsx
<div style={getItemAnim(startFrame, 0)} className="col-span-7 flex justify-center">
  <ShowcaseDisplay
    src="screens/16_dosen_rekap_gradebook.png"
    theme="dark" // atau "light"
  />
</div>
```
- Bingkai: `rounded-2xl border-4 border-slate-700/50 shadow-2xl`
- Efek Masuk: Skala pegas lembut `spring` dari `0.96` ke `1.0` dengan `damping: 16`, `stiffness: 90`.

### 4. Cover & Final CTA (Tepat 3 Baris Bersih)
```tsx
<div className="relative z-10 flex-1 flex flex-col justify-center items-center px-24 max-w-[1720px] mx-auto w-full text-center my-auto">
  {/* Logo S Badge */}
  <div style={getItemAnim(0, 0)} className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-[#102f50] font-bold text-4xl shadow-2xl mb-8">
    S
  </div>

  {/* Baris 1: Judul Utama Satu Baris (font-extrabold) */}
  <h1 className="text-[58px] font-extrabold tracking-tight text-white leading-tight whitespace-nowrap">
    <WordByWord text="Melampaui Presensi dan Angka" startFrame={6} durationInFrames={40} highlightWords={["Presensi", "Angka"]} />
  </h1>

  {/* Baris 2: Subjudul Satu Baris (font-normal kontras) */}
  <div className="mt-5 text-3xl font-normal text-slate-300 whitespace-nowrap">
    <WordByWord text="Membuktikan Ketercapaian Kompetensi Nyata Mahasiswa" startFrame={48} durationInFrames={45} highlightWords={["Kompetensi", "Nyata"]} />
  </div>

  {/* Baris 3: Nama Institusi Satu Baris */}
  <div style={getItemAnim(80, 0)} className="mt-8 text-base font-semibold text-slate-400 tracking-[0.25em] uppercase whitespace-nowrap">
    Institut Teknologi Senggarang
  </div>
</div>
```

---

## 5. PROTOKOL PENGUJIAN & VERIFIKASI SEBELUM SELESAI

Setiap pembuatan atau modifikasi video **wajib** melalui 3 tahapan verifikasi:
1. **Render Still Frames Kunci**:
   ```bash
   npx remotion still src/index.ts <Composition-ID> test_frames/test_frame.png --frame=<NomorFrame>
   ```
2. **Inspeksi Visual**: Gunakan alat bantu gambar untuk memeriksa bahwa tidak ada teks yang menumpuk di tengah, warna tidak menabrak, dan badge angka tampil sempurna.
3. **Verifikasi Kompilasi Penuh**:
   ```bash
   npm run build
   ```
   Pastikan menghasilkan **Exit Code: 0** tanpa kesalahan tipe TypeScript atau masalah bundler.
