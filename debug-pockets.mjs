import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  const logs = [];
  const errors = [];

  page.on('console', msg => {
    logs.push(`[${msg.type()}] ${msg.text()}`);
  });
  page.on('pageerror', err => {
    errors.push(`[PAGEERROR] ${err.message}`);
  });

  console.log('=== Navigating to /game ===');
  await page.goto('http://localhost:4173/game', { waitUntil: 'networkidle' });
  await page.waitForTimeout(2000);

  // Check initial DOM state
  const menuBtn = await page.$('.qm-toggle');
  console.log('MENU button found:', !!menuBtn);

  // Check pinia state
  const piniaState = await page.evaluate(() => {
    if (window.__pinia) {
      const stores = Object.keys(window.__pinia.state.value);
      const result = {};
      stores.forEach(key => {
        result[key] = window.__pinia.state.value[key];
      });
      return result;
    }
    return null;
  });
  console.log('Pinia state:', JSON.stringify(piniaState, null, 2));

  console.log('\n=== Clicking MENU button ===');
  await menuBtn.click();
  await page.waitForTimeout(500);

  const menuPanel = await page.$('.qm-panel');
  console.log('QM panel visible:', !!menuPanel);

  const pcRow = await page.$('.qm-row:first-child');
  if (pcRow) {
    const text = await pcRow.innerText();
    console.log('First row text:', text);
  }

  // Find PC button
  const rows = await page.$$('.qm-row');
  let pcBtn = null;
  for (const row of rows) {
    const txt = await row.innerText();
    if (txt.includes('PC')) {
      pcBtn = row;
      console.log('Found PC button:', txt.trim());
      break;
    }
  }

  if (pcBtn) {
    console.log('\n=== Clicking PC ===');
    await pcBtn.click();
    await page.waitForTimeout(1000);

    const pocketOpen = await page.evaluate(() => {
      if (window.__pinia) {
        return window.__pinia.state.value;
      }
      return null;
    });
    console.log('Pinia state after PC click:', JSON.stringify(pocketOpen, null, 2));

    const pcPocket = await page.$('.pc-pocket');
    console.log('PCPocket in DOM:', !!pcPocket);

    // Screenshot
    await page.screenshot({ path: 'debug-after-pc-click.png', fullPage: false });
    console.log('Screenshot saved: debug-after-pc-click.png');
  } else {
    console.log('ERROR: PC button not found in menu');
  }

  console.log('\n=== Console logs ===');
  logs.forEach(l => console.log(l));

  console.log('\n=== Page errors ===');
  errors.forEach(e => console.log(e));

  if (errors.length === 0 && logs.filter(l => l.includes('[error]')).length === 0) {
    console.log('No errors detected.');
  }

  await browser.close();
})();
