const { chromium } = require('playwright');
const assert = require('node:assert/strict');
(async () => {
 const browser = await chromium.launch({headless:true, ...(process.env.CHROMIUM_PATH ? {executablePath:process.env.CHROMIUM_PATH} : {})});
 const page = await browser.newPage();
 const errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 await page.goto(`${process.env.SALE_URL || 'http://127.0.0.1:8000'}/mahasiswa/assignment/1/code`);
 await page.locator('[data-run-code]:not([disabled])').waitFor();
 const input = page.locator('.cm-content');
 async function code(text){await input.click();await page.keyboard.press('ControlOrMeta+A');await page.keyboard.insertText(text);await page.locator('[data-clear-terminal]').click();}
 await code("for i in range(1, 6): print('*' * i)");
 await page.locator('[data-run-code]').click();
 await page.waitForFunction(()=>document.querySelector('[data-terminal-output]').textContent.includes('Selesai'),null,{timeout:70000});
 const stars = await page.locator('[data-terminal-output]').innerText();
 assert.match(stars,/exit code 0/);
 assert.match(stars,/\*\*\*\*\*/);
 assert.doesNotMatch(stars,/TestBST|BinaryTree|NameError|Sedang|Menjalankan kode/);
 assert.equal(await page.locator('[data-execution-progress]').count(), 0);
 await code(`class Node:
    def __init__(self, key):
        self.value, self.left, self.right = key, None, None
class BinaryTree:
    def insert(self, root, key):
        if root is None: return Node(key)
        if key < root.value: root.left = self.insert(root.left, key)
        else: root.right = self.insert(root.right, key)
        return root
print('browser test output')`);
 await page.locator('[data-test-code]').click();
 await page.waitForFunction(()=>/Selesai|gagal|dihentikan|Failed/.test(document.querySelector('[data-terminal-output]').textContent),null,{timeout:70000});
 const output=await page.locator('[data-terminal-output]').innerText();
 assert.match(output,/exit code 0/); assert.match(output,/Ran 4 tests/);assert.match(output,/browser test output/);
 await code('while True: pass');
 await page.locator('[data-run-code]').click();
 await page.waitForFunction(()=>document.querySelector('[data-terminal-output]').textContent.includes('menjalankan main.py'),null,{timeout:60000});
 await page.locator('[data-stop-code]').click();
 await page.waitForFunction(()=>!document.querySelector('[data-run-code]').disabled);
 const stopped=await page.locator('[data-terminal-output]').innerText();
 assert.match(stopped,/Eksekusi dihentikan/);
 assert.equal(await page.locator('[data-execution-progress]').count(), 0);
 await page.locator('[data-clear-terminal]').click();
 await page.locator('[data-run-code]').click();
 await page.waitForFunction(()=>document.querySelector('[data-terminal-output]').textContent.includes('setelah 10 detik'),null,{timeout:70000});
 assert.equal(await page.locator('[data-execution-progress]').count(), 0);
 assert.deepEqual(errors, []);
 console.log(JSON.stringify({output,stopWorks:true,timeoutWorks:true,errors}));
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
