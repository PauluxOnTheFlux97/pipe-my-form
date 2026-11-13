/* Pipe My Form — progressive enhancement of the server-rendered WordPress flow editor. */
(() => {
  'use strict';
  const root = document.getElementById('pmf-builder-nodes');
  const form = document.getElementById('pmf-builder-form');
  const add = document.getElementById('pmf-add-node');
  if (!root || !form || !add) return;

  let nextNode = 0;
  let nextEdge = 0;

  const scan = () => {
    for (const input of form.querySelectorAll('[name]')) {
      const matches = input.name.match(/^nodes\[(\d+)\](?:\[edges\]\[(\d+)\])?/);
      if (matches) {
        nextNode = Math.max(nextNode, Number(matches[1]) + 1);
        if (matches[2]) nextEdge = Math.max(nextEdge, Number(matches[2]) + 1);
      }
    }
  };
  scan();
  const id = (name) => String(name).replace(/[^a-zA-Z0-9_-]/g, '-');
  const options = (values) => values.map(([value, label]) =>
    '<option value="' + value + '">' + label + '</option>'
  ).join('');
  const field = (name, value, placeholder = '') =>
    '<input name="' + name + '" value="' + value + '" placeholder="' + placeholder + '">';

  function edge(rootName, key) {
    const prefix = rootName + '[edges][' + key + ']';
    const node = document.createElement('div');
    node.className = 'pmf-edge';
    node.style.margin = '8px 0';
    node.innerHTML =
      '<label>Go to ID ' + field(prefix + '[to]', '', 'next_question') + '</label> ' +
      '<label>When <select name="' + prefix + '[operator]">' +
      options([['', 'Always (fallback)'], ['equals', 'Equals'], ['not_equals', 'Does not equal'], ['contains', 'Contains']]) +
      '</select></label> ' +
      '<label>Value ' + field(prefix + '[value]', '') + '</label> ' +
      '<button type="button" class="button-link-delete pmf-delete-edge">Remove</button>';
    return node;
  }

  add.addEventListener('click', () => {
    if (root.querySelectorAll('.pmf-node').length >= 100) {
      window.alert('Maximum of 100 questions per flow.');
      return;
    }
    const key = nextNode++;
    const prefix = 'nodes[' + key + ']';
    const container = document.createElement('fieldset');
    container.className = 'pmf-node';
    container.style.cssText = 'background:#fff;border:1px solid #c3c4c7;padding:16px;margin:12px 0;max-width:1000px';
    container.innerHTML =
      '<legend><strong>Question</strong></legend>' +
      '<p><label>Unique ID ' + field(prefix + '[id]', 'question_' + key) + '</label> ' +
      '<label>Type <select name="' + prefix + '[type]">' +
      options([['text', 'Text'], ['textarea', 'Long text'], ['email', 'Email'], ['tel', 'Phone'],
        ['single_choice', 'Single choice'], ['multiple_choice', 'Multiple choice'],
        ['consent', 'Consent'], ['info', 'Info screen']]) +
      '</select></label></p>' +
      '<p><label>Question / screen text ' + field(prefix + '[label]', '') + '</label></p>' +
      '<p><label><input type="checkbox" name="' + prefix + '[required]" value="1"> Required</label></p>' +
      '<p><label>Answer options, one per line (single or multiple choice)<br>' +
      '<textarea rows="3" style="width:100%;max-width:700px" name="' + prefix + '[options]"></textarea></label></p>' +
      '<strong>Connections</strong> <small>(empty destination = finish; leave operator blank for fallback)</small>' +
      '<div class="pmf-edges"></div>' +
      '<p><button type="button" class="button pmf-add-edge">+ Add connection</button></p>' +
      '<p><button type="button" class="button pmf-move-up">Move up</button> ' +
      '<button type="button" class="button pmf-move-down">Move down</button> ' +
      '<button type="button" class="button-link-delete pmf-delete-node">Remove question</button></p>';
    root.append(container);
    const nameInput = container.querySelector('input[name$="[label]"]');
    if (nameInput) nameInput.focus();
  });

  root.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    const action = event.target.closest('button');
    if (!action) return;
    const node = action.closest('.pmf-node');
    if (!node) return;
    if (action.classList.contains('pmf-add-edge')) {
      const input = node.querySelector('input[name$="[id]"]');
      const match = input?.name.match(/^(nodes\[\d+\])/);
      const edges = node.querySelector('.pmf-edges');
      if (!match || !edges) return;
      if (edges.children.length >= 50) {
        window.alert('Maximum 50 connections.');
        return;
      }
      edges.append(edge(match[1], nextEdge++));
    } else if (action.classList.contains('pmf-delete-edge')) {
      action.closest('.pmf-edge')?.remove();
    } else if (action.classList.contains('pmf-delete-node')) {
      if (root.querySelectorAll('.pmf-node').length <= 1) {
        window.alert('A flow needs at least one question.');
        return;
      }
      if (window.confirm('Remove this question? Connections pointing to it must also be updated.')) node.remove();
    } else if (action.classList.contains('pmf-move-up')) {
      if (node.previousElementSibling) root.insertBefore(node, node.previousElementSibling);
    } else if (action.classList.contains('pmf-move-down')) {
      if (node.nextElementSibling) root.insertBefore(node.nextElementSibling, node);
    }
  });
})();
