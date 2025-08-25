const dropArea = document.getElementById("drop-area");
const input = document.getElementById("product_image");
const preview = document.getElementById("preview-image");
const removeBtn = document.getElementById("remove-image");
const dropText = document.getElementById("drop-text");

dropArea.addEventListener("dragover", (e) => {
  e.preventDefault();
  dropArea.style.background = "#eee";
});

dropArea.addEventListener("dragleave", (e) => {
  e.preventDefault();
  dropArea.style.background = "";
});

dropArea.addEventListener("drop", (e) => {
  e.preventDefault();
  dropArea.style.background = "";
  const file = e.dataTransfer.files[0];
  handleFile(file);
});

input.addEventListener("change", () => {
  const file = input.files[0];
  handleFile(file);
});

removeBtn.addEventListener("click", (e) => {
  e.stopPropagation();
  input.value = "";
  preview.src = "";
  preview.style.display = "none";
  removeBtn.style.display = "none";
  dropText.style.display = "block";
});

function handleFile(file) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function (e) {
    preview.src = e.target.result;
    preview.style.display = "block";
    removeBtn.style.display = "inline-block";
    dropText.style.display = "none";
  };
  reader.readAsDataURL(file);
}

document
  .getElementById("aminh-simple-add-form")
  .addEventListener("submit", function (e) {
    const regular = parseFloat(document.getElementById("regular_price").value);
    const sale = parseFloat(document.getElementById("sale_price").value);

    if (regular < 0 || sale < 0) {
      e.preventDefault();
      alert("قیمت‌ها نمی‌توانند کمتر از صفر باشند.");
    }
  });

const form = document.getElementById("aminh-simple-add-form");
const titleInput = document.getElementById("product_title");
const categoryInput = document.getElementById("product_category");
const stockInput = document.getElementById("stock_status");
const imageInput = document.getElementById("product_image");
const regularInput = document.getElementById("regular_price");
const saleInput = document.getElementById("sale_price");

function showError(input, message) {
  let error = input.parentElement.querySelector(".aminh-error");
  if (!error) {
    error = document.createElement("div");
    error.className = "aminh-error";
    error.style.color = "red";
    error.style.fontSize = "12px";
    error.style.marginTop = "4px";
    input.parentElement.appendChild(error);
  }
  error.textContent = message;
}

function clearError(input) {
  const error = input.parentElement.querySelector(".aminh-error");
  if (error) error.remove();
}

function validateForm() {
  let valid = true;

  if (!titleInput.value.trim()) {
    showError(titleInput, "شماره سیم کارت نمی‌تواند خالی باشد.");
    valid = false;
  } else {
    clearError(titleInput);
  }

  if (!categoryInput.value) {
    showError(categoryInput, "یک دسته بندی انتخاب کنید.");
    valid = false;
  } else {
    clearError(categoryInput);
  }

  if (!stockInput.value) {
    showError(stockInput, "وضعیت شماره را انتخاب کنید.");
    valid = false;
  } else {
    clearError(stockInput);
  }

  if (!imageInput.files || imageInput.files.length === 0) {
    showError(imageInput, "یک تصویر انتخاب کنید.");
    valid = false;
  } else {
    clearError(imageInput);
  }

  let regular = parseFloat(regularInput.value);
  let sale = parseFloat(saleInput.value);

  if (isNaN(regular) || regular < 1) {
    regular = 1;
    showError(regularInput, "قیمت عادی نمی‌تواند کمتر از 1 باشد.");
    valid = false;
  } else {
    clearError(regularInput);
  }
  regularInput.value = regular;

  if (sale > regular) {
    sale = regular;
    showError(saleInput, "قیمت تخفیف نمی‌تواند بیشتر از قیمت عادی باشد.");
    valid = false;
  } else {
    clearError(saleInput);
  }
  saleInput.value = sale;

  return valid;
}

function sanitizeRegularPrice(input) {
  input.addEventListener("input", () => {
    let val = input.value.replace(/\D/g, "");
    val = val.replace(/^0+/, ""); 
    if (val === "") val = ""; 
    input.value = val;
  });
}

function sanitizeSalePrice(input, regularInput) {
  input.addEventListener("input", () => {
    let val = input.value.replace(/\D/g, "");
    val = val.replace(/^0+/, "");

    if (val === "") {
      input.value = "";
      return;
    }

    val = parseInt(val);
    if (val < 1) val = 1;
    const regularVal = parseInt(regularInput.value);
    if (val > regularVal) val = regularVal;
    input.value = val;
  });
}

sanitizeRegularPrice(regularInput);
sanitizeSalePrice(saleInput, regularInput);

form.addEventListener("submit", function (e) {
  if (!validateForm()) {
    e.preventDefault();
  }
});

document.addEventListener('DOMContentLoaded', function() {
    const existingImage = document.querySelector('#preview-image[src]');
    if (existingImage && existingImage.src) {
        preview.style.display = "block";
        removeBtn.style.display = "inline-block";
        dropText.style.display = "none";
    }
});
