/**
 * Tests for JkPublishPages.js (Node test runner + jsdom)
 * Run: npm install && npm test
 */
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {JSDOM} from 'jsdom';

const script = readFileSync(new URL('../../JkPublishPages.js', import.meta.url), 'utf8');

// markup like in the module configuration (toggle link inside the Inputfield wrapper of the checkboxes)
function createDom(checked = [false, false, false], extraMarkup = '', beforeScript = '') {
    const boxes = checked.map((c, i) =>
        `<label><input type="checkbox" name="input_templates[]" value="t${i}"${c ? ' checked' : ''}> t${i}</label>`).join('');
    const html = `<!DOCTYPE html><body>
        <li class="Inputfield" id="templates">
            <button type="button" class="jkpp-toggle-link" data-jkpp-toggle>Un/check all</button>
            ${boxes}
        </li>
        <li class="Inputfield" id="other"><input type="checkbox" name="other" value="1"></li>
        ${extraMarkup}
        <script>${beforeScript}</script>
        <script>${script}</script>
    </body>`;
    return new JSDOM(html, {runScripts: 'dangerously'});
}

// wait until the DOM has been loaded (the script initializes itself on DOMContentLoaded, like in the browser)
function loaded(dom) {
    return new Promise((resolve) => {
        if (dom.window.document.readyState !== 'loading') return resolve(dom);
        dom.window.document.addEventListener('DOMContentLoaded', () => resolve(dom));
    });
}

const states = (dom) => [...dom.window.document.querySelectorAll('#templates input')].map((i) => i.checked);
const click = (dom) => dom.window.document.querySelector('[data-jkpp-toggle]').click();

test('first click checks all, second click unchecks all', async () => {
    const dom = await loaded(createDom([false, true, false]));
    click(dom);
    assert.deepEqual(states(dom), [true, true, true]);
    click(dom);
    assert.deepEqual(states(dom), [false, false, false]);
});

test('if all checkboxes are already checked, the first click unchecks all', async () => {
    const dom = await loaded(createDom([true, true, true]));
    click(dom);
    assert.deepEqual(states(dom), [false, false, false]);
});

test('checkboxes outside of the Inputfield wrapper are not changed', async () => {
    const dom = await loaded(createDom([false, false]));
    click(dom);
    assert.equal(dom.window.document.querySelector('#other input').checked, false);
});

test('a change event is fired for every changed checkbox only', async () => {
    const dom = await loaded(createDom([true, false, false]));
    let events = 0;
    dom.window.document.querySelector('#templates').addEventListener('change', () => events++);
    click(dom);
    assert.equal(events, 2);
});

test('disabled checkboxes are not changed', async () => {
    const dom = await loaded(createDom([false, false]));
    dom.window.document.querySelectorAll('#templates input')[1].disabled = true;
    click(dom);
    assert.deepEqual(states(dom), [true, false]);
});

test('window.onload of other scripts is not overwritten', async () => {
    const dom = createDom([false], '', 'window.onload = function () { window.otherOnload = true; };');
    assert.equal(typeof dom.window.onload, 'function');
    assert.match(dom.window.onload.toString(), /otherOnload/);
});

test('only one global name is used (namespace JkPublishPages)', async () => {
    const dom = await loaded(createDom([false]));
    assert.equal(typeof dom.window.JkPublishPages, 'object');
    assert.equal(typeof dom.window.JkPublishPages.toggle, 'function');
    for (const name of ['checkAll', 'uncheckAll', 'checkboxes', 'toggle']) {
        assert.equal(name in dom.window, false, `global "${name}" must not exist`);
    }
});

test('initializing twice does not bind the click handler twice', async () => {
    const dom = await loaded(createDom([false, false]));
    dom.window.JkPublishPages.initToggles();
    click(dom);
    assert.deepEqual(states(dom), [true, true]);
});

test('no error on pages without toggle link', async () => {
    const dom = await loaded(new JSDOM(`<!DOCTYPE html><body><input type="checkbox" name="input_templates[]"><script>${script}</script></body>`,
        {runScripts: 'dangerously'}));
    assert.equal(typeof dom.window.JkPublishPages, 'object');
});
