// Replays the actual polling renderer with a DOM stub; no browser or script payload is executed.
// A passing result demonstrates unsafe HTML insertion, not a successful browser exploit run.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync('resources/views/learning/course.blade.php', 'utf8');
const begin = source.indexOf('async function refreshChatStream(');
const end = source.indexOf("document.addEventListener('click'", begin);
assert.ok(begin > 0 && end > begin);
const payload = '<img src=x onerror="window.auditMarker=1">';
const pinnedList = { innerHTML: '' };
const chatMessages = {
  innerHTML: '', scrollHeight: 100, clientHeight: 100, scrollTop: 0,
  querySelector: () => null, querySelectorAll: () => [],
};
const context = vm.createContext({
  courseId: 1, totalBadge: null,
  pinnedContainer: { classList: { remove() {}, add() {} } }, pinnedList,
  pinnedCountBadge: null, isDosenUser: false, chatMessages,
  document: { getElementById: () => null },
  fetch: async () => ({ ok: true, json: async () => ({
    success: true,
    pinned_messages: [{ id: 1, is_me: false, author: 'Audit', content: payload }],
    messages: [{ id: 2, is_me: false, author: 'Audit', content: 'Safe reply', role: 'mahasiswa', time: '12:00',
      reply_to: { sender_name: 'Audit', excerpt: payload } }],
  }) }),
});
vm.runInContext(source.slice(begin, end) + '\nglobalThis.auditRefresh = refreshChatStream;', context);
await context.auditRefresh();
assert.ok(pinnedList.innerHTML.includes(payload), 'Pinned message inserted as raw markup');
assert.ok(chatMessages.innerHTML.includes(payload), 'Reply excerpt inserted as raw markup');
console.log('CONFIRMED: raw user markup reaches pinnedList.innerHTML and reply excerpt in chatMessages.innerHTML.');
console.log('Boundary: renderer tested in a DOM stub; JavaScript execution in a real browser was not tested.');
