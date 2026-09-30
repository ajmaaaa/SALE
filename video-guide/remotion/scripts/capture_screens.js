import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const OUT_DIR = path.resolve('public/screens');
fs.mkdirSync(OUT_DIR, { recursive: true });

async function loginAndCapture(browser, loginId, password, pagesToVisit) {
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    const client = await page.target().createCDPSession();
    await client.send('Network.clearBrowserCookies');

    await page.goto('http://127.0.0.1:8080/login', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('input[name="login_id"]', { timeout: 4000 });
    
    await page.type('input[name="login_id"]', loginId);
    await page.type('input[name="password"]', password);
    
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.click('button[type="submit"]')
    ]);

    await new Promise(r => setTimeout(r, 600));

    for (const item of pagesToVisit) {
        console.log(`  -> Navigating to: ${item.name} (${item.url})`);
        if (page.url() !== item.url) {
            await page.goto(item.url, { waitUntil: 'domcontentloaded' }).catch(() => {});
            await new Promise(r => setTimeout(r, 600));
        }
        await page.screenshot({ path: path.join(OUT_DIR, item.filename) });
        console.log(`     Saved ${item.filename}`);
    }

    await page.close();
}

async function run() {
    console.log('Launching Brave...');
    const browser = await puppeteer.launch({
        executablePath: '/usr/bin/brave',
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-gpu',
            '--window-size=1920,1080',
        ],
        defaultViewport: { width: 1920, height: 1080 }
    });

    // 1. Login Page (Blank & Typed)
    console.log('1. Capturing Login Page...');
    {
        const page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });
        await page.goto('http://127.0.0.1:8080/login', { waitUntil: 'domcontentloaded' });
        await new Promise(r => setTimeout(r, 600));
        await page.screenshot({ path: path.join(OUT_DIR, '01_login_blank.png') });

        await page.type('input[name="login_id"]', '231011401234');
        await page.type('input[name="password"]', 'password');
        await page.screenshot({ path: path.join(OUT_DIR, '02_login_typed.png') });
        await page.close();
    }

    // 2. Mahasiswa
    console.log('2. Mahasiswa...');
    await loginAndCapture(browser, '231011401234', 'password', [
        { name: 'Mahasiswa Dashboard', url: 'http://127.0.0.1:8080/mahasiswa/dashboard', filename: '03_mahasiswa_dashboard.png' },
        { name: 'Mahasiswa Course', url: 'http://127.0.0.1:8080/course', filename: '04_mahasiswa_course.png' },
    ]);

    // 3. Dosen
    console.log('3. Dosen...');
    await loginAndCapture(browser, '198501012010121001', 'password', [
        { name: 'Dosen Dashboard', url: 'http://127.0.0.1:8080/dosen/dashboard', filename: '05_dosen_dashboard.png' },
        { name: 'Dosen Course', url: 'http://127.0.0.1:8080/dosen/course', filename: '06_dosen_course.png' },
        { name: 'Dosen Penilaian', url: 'http://127.0.0.1:8080/dosen/penilaian', filename: '07_dosen_penilaian.png' },
        { name: 'Dosen Rekap Nilai', url: 'http://127.0.0.1:8080/dosen/rekap', filename: '08_dosen_rekap.png' },
    ]);

    // 4. Admin Prodi
    console.log('4. Admin Prodi...');
    await loginAndCapture(browser, 'AP001', 'password', [
        { name: 'Admin Prodi Dashboard', url: 'http://127.0.0.1:8080/admin-prodi/dashboard', filename: '09_adminprodi_dashboard.png' },
        { name: 'Admin Prodi CPMK', url: 'http://127.0.0.1:8080/admin-prodi/kurikulum?prodi_id=1&tab=cpmk', filename: '10_adminprodi_cpmk.png' },
        { name: 'Admin Prodi Kelas', url: 'http://127.0.0.1:8080/admin-prodi/akademik/kelas?prodi_id=1&semester_id=2', filename: '11_adminprodi_kelas.png' },
    ]);

    // 5. Admin Sistem
    console.log('5. Admin Sistem...');
    await loginAndCapture(browser, 'ADM001', 'password', [
        { name: 'Admin Sistem Dashboard', url: 'http://127.0.0.1:8080/admin/dashboard', filename: '12_admin_dashboard.png' },
    ]);

    console.log('DONE! All real screen captures successfully captured.');
    await browser.close();
}

run().catch(console.error);
