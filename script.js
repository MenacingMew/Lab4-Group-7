// Confirm before destructive actions (delete / clear completed)
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    if (!window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });
});

// Live character counter for the description field
var desc = document.getElementById('task_description');
var counter = document.getElementById('desc-counter');
if (desc && counter) {
  var update = function () {
    counter.textContent = desc.value.length + '/255';
    counter.classList.toggle('over', desc.value.length > 255);
  };
  desc.addEventListener('input', update);
  update();
}

// Focus the name field when editing so the user can type right away
var params = new URLSearchParams(window.location.search);
if (params.has('edit')) {
  var name = document.getElementById('task_name');
  if (name) { name.focus(); name.select(); }
}
