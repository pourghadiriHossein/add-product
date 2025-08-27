// -------------------- Drop Area --------------------
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

// -------------------- Product Title --------------------
const titleInput = document.getElementById("product_title");
titleInput.addEventListener("input", () => {
  let val = titleInput.value.replace(/[^\d\s]/g, "");
  val = val.replace(/\s+/g, " ");
  titleInput.value = val;
  titleInput.style.direction = "ltr";
});

// -------------------- Price Inputs --------------------
const regularInput = document.getElementById("regular_price");
const saleInput = document.getElementById("sale_price");

function formatPriceInput(input) {
  input.addEventListener("input", () => {
    let raw = input.value.replace(/\D/g, "");
    if (!raw) {
      input.value = "";
      return;
    }
    input.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  });

  input.form.addEventListener("submit", () => {
    input.value = input.value.replace(/,/g, "");
  });
}

formatPriceInput(regularInput);
formatPriceInput(saleInput);

// -------------------- Form Validation --------------------
const form = document.getElementById("aminh-simple-add-form");
const imageInput = document.getElementById("product_image");

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

  // شماره سیم کارت
  if (!titleInput.value.trim()) {
    showError(titleInput, "شماره سیم کارت نمی‌تواند خالی باشد.");
    valid = false;
  } else {
    clearError(titleInput);
  }

  const productId = document.querySelector('input[name="product_id"]').value;
  if (productId == "0" && (!imageInput.files || imageInput.files.length === 0)) {
    showError(imageInput, "یک تصویر انتخاب کنید.");
    valid = false;
  } else {
    clearError(imageInput);
  }

  const regular = parseInt(regularInput.value.replace(/,/g, ""), 10) || 0;
  if (regular < 1) {
    showError(regularInput, "قیمت عادی نمی‌تواند کمتر از 1 باشد.");
    valid = false;
  } else {
    clearError(regularInput);
  }

  const sale = parseInt(saleInput.value.replace(/,/g, ""), 10) || 0;
  if (sale > 0 && sale > regular) {
    showError(saleInput, "قیمت تخفیف نمی‌تواند بیشتر از قیمت عادی باشد.");
    valid = false;
  } else {
    clearError(saleInput);
  }

  return valid;
}

let formIsSubmitting = false;

form.addEventListener("submit", (e) => {
  if (formIsSubmitting) {
    e.preventDefault();
    return;
  }
  
  if (!validateForm()) {
    e.preventDefault();
    const firstError = document.querySelector(".aminh-error");
    if (firstError) {
      firstError.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    return;
  }
  
  formIsSubmitting = true;
  
  const submitButton = form.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.textContent = 'در حال ارسال...';
});

const allInputs = form.querySelectorAll("input, select");
allInputs.forEach(input => {
  input.addEventListener("input", () => {
    clearError(input);
  });
  input.addEventListener("change", () => {
    clearError(input);
  });
});

// -------------------- Preload existing image --------------------
document.addEventListener("DOMContentLoaded", function () {
  const existingImage = document.querySelector("#preview-image");

  if (existingImage && existingImage.src) {
    preview.style.display = "block";
    removeBtn.style.display = "inline-block";
    dropText.style.display = "none";
  }
});
