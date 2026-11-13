document.addEventListener("DOMContentLoaded", function () {
  // 1. Tab State Retention
  const activeTabId = localStorage.getItem("activeTab");
  if (activeTabId) {
    const tabTrigger = document.querySelector(
      `[data-bs-target="${activeTabId}"]`,
    );
    if (tabTrigger) {
      const tab = new bootstrap.Tab(tabTrigger);
      tab.show();
    }
  }
  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((tabEl) => {
    tabEl.addEventListener("shown.bs.tab", function (event) {
      const targetTab = event.target.getAttribute("data-bs-target");
      localStorage.setItem("activeTab", targetTab);
    });
  });

  // Set minimum date for checkout/transactions if element exists
  const newTxDate = document.getElementById("newTxDate");
  if (newTxDate) {
    newTxDate.min = new Date().toISOString().split("T")[0];
  }

  // Multi-item checkout row controls
  const checkoutContainer = document.getElementById("checkoutItemsContainer");
  const addCheckoutItemBtn = document.getElementById("btnAddCheckoutItem");
  const checkoutTemplate = document.getElementById("checkoutItemTemplate");
  const checkoutPreviewList = document.getElementById("checkoutPreviewList");
  const checkoutPreviewEmpty = document.getElementById("checkoutPreviewEmpty");
  const checkoutPreviewCount = document.getElementById("checkoutPreviewCount");
  let checkoutRowCounter = 0;

  function ensureRowId(row) {
    if (!row.dataset.rowId) {
      checkoutRowCounter += 1;
      row.dataset.rowId = String(checkoutRowCounter);
    }
  }

  function applyQuantityLimitForRow(row) {
    const select = row.querySelector(".checkout-eq-select");
    const qtyInput = row.querySelector(".checkout-qty-input");
    if (!select || !qtyInput) {
      return;
    }

    const selectedOption = select.options[select.selectedIndex];
    const maxStock = parseInt(selectedOption?.dataset?.stock || "0", 10);
    if (maxStock > 0) {
      qtyInput.max = String(maxStock);
      if (parseInt(qtyInput.value || "1", 10) > maxStock) {
        qtyInput.value = String(maxStock);
      }
    } else {
      qtyInput.removeAttribute("max");
    }
  }

  function refreshRemoveButtons() {
    if (!checkoutContainer) {
      return;
    }

    const rows = checkoutContainer.querySelectorAll(".checkout-item-row");
    rows.forEach((row, index) => {
      const removeBtn = row.querySelector(".checkout-remove-item");
      if (!removeBtn) {
        return;
      }

      if (rows.length === 1 && index === 0) {
        removeBtn.classList.add("d-none");
      } else {
        removeBtn.classList.remove("d-none");
      }
    });
  }

  function refreshEquipmentOptionAvailability() {
    if (!checkoutContainer) {
      return;
    }

    const selectedValues = new Set();
    checkoutContainer.querySelectorAll(".checkout-eq-select").forEach((select) => {
      if (select.value) {
        selectedValues.add(select.value);
      }
    });

    checkoutContainer.querySelectorAll(".checkout-eq-select").forEach((select) => {
      Array.from(select.options).forEach((option) => {
        if (!option.value) {
          return;
        }
        option.disabled = selectedValues.has(option.value) && select.value !== option.value;
      });
    });
  }

  function renderCheckoutPreview() {
    if (!checkoutContainer || !checkoutPreviewList || !checkoutPreviewEmpty || !checkoutPreviewCount) {
      return;
    }

    const rows = checkoutContainer.querySelectorAll(".checkout-item-row");
    checkoutPreviewList.innerHTML = "";

    let renderedCount = 0;
    rows.forEach((row) => {
      ensureRowId(row);

      const select = row.querySelector(".checkout-eq-select");
      const qtyInput = row.querySelector(".checkout-qty-input");
      if (!select || !qtyInput || !select.value) {
        return;
      }

      renderedCount += 1;
      const selectedOption = select.options[select.selectedIndex];
      const itemName = selectedOption ? selectedOption.text : "Unknown item";

      const li = document.createElement("li");
      li.className = "list-group-item d-flex justify-content-between align-items-center bg-transparent px-0";
      li.innerHTML =
        `<div><span class="fw-semibold">${itemName}</span><div class="small text-muted">Qty: ${qtyInput.value}</div></div>` +
        `<button type="button" class="btn btn-sm btn-outline-danger checkout-preview-delete" data-row-id="${row.dataset.rowId}">Remove</button>`;
      checkoutPreviewList.appendChild(li);
    });

    checkoutPreviewCount.textContent = String(renderedCount);
    checkoutPreviewEmpty.classList.toggle("d-none", renderedCount > 0);
  }

  function refreshCheckoutUiState() {
    refreshRemoveButtons();
    refreshEquipmentOptionAvailability();
    renderCheckoutPreview();
  }

  if (checkoutContainer) {
    checkoutContainer.querySelectorAll(".checkout-item-row").forEach((row) => {
      ensureRowId(row);
      applyQuantityLimitForRow(row);
    });

    checkoutContainer.addEventListener("change", function (event) {
      if (event.target && event.target.classList.contains("checkout-eq-select")) {
        const row = event.target.closest(".checkout-item-row");
        if (row) {
          applyQuantityLimitForRow(row);
        }
        refreshCheckoutUiState();
      }
    });

    checkoutContainer.addEventListener("input", function (event) {
      if (event.target && event.target.classList.contains("checkout-qty-input")) {
        const row = event.target.closest(".checkout-item-row");
        if (row) {
          applyQuantityLimitForRow(row);
        }
        renderCheckoutPreview();
      }
    });

    checkoutContainer.addEventListener("click", function (event) {
      if (event.target && event.target.classList.contains("checkout-remove-item")) {
        const row = event.target.closest(".checkout-item-row");
        if (row) {
          row.remove();
          refreshCheckoutUiState();
        }
      }
    });

    refreshCheckoutUiState();
  }

  if (checkoutPreviewList && checkoutContainer) {
    checkoutPreviewList.addEventListener("click", function (event) {
      if (event.target && event.target.classList.contains("checkout-preview-delete")) {
        const rowId = event.target.getAttribute("data-row-id");
        const row = checkoutContainer.querySelector(`.checkout-item-row[data-row-id="${rowId}"]`);
        if (row) {
          row.remove();
          refreshCheckoutUiState();
        }
      }
    });
  }

  if (addCheckoutItemBtn && checkoutContainer && checkoutTemplate) {
    addCheckoutItemBtn.addEventListener("click", function () {
      const fragment = checkoutTemplate.content.cloneNode(true);
      checkoutContainer.appendChild(fragment);

      const rows = checkoutContainer.querySelectorAll(".checkout-item-row");
      const newestRow = rows[rows.length - 1];
      if (newestRow) {
        ensureRowId(newestRow);
        applyQuantityLimitForRow(newestRow);
      }
      refreshCheckoutUiState();
    });
  }

});

// --- 5. GLOBAL HELPER FUNCTIONS ---
function editBorrower(id, no, name, type) {
  document.getElementById("actBorrower").value = "update_borrower";
  document.getElementById("idBorrower").value = id;
  document.getElementById("noBorrower").value = no;
  document.getElementById("nameBorrower").value = name;
  document.getElementById("typeBorrower").value = type;
  document.getElementById("btnSubmitBorrower").innerText = "Update";
  document.getElementById("btnCancelBorrower").classList.remove("d-none");
}

function resetBorrowerForm() {
  document.getElementById("actBorrower").value = "create_borrower";
  document.getElementById("idBorrower").value = "";
  document.getElementById("formBorrower").reset();
  document.getElementById("btnSubmitBorrower").innerText = "Save";
  document.getElementById("btnCancelBorrower").classList.add("d-none");
}

function editEq(id, prop, name, qty) {
  document.getElementById("actEq").value = "update_eq";
  document.getElementById("idEq").value = id;
  document.getElementById("propEq").value = prop;
  document.getElementById("nameEq").value = name;
  document.getElementById("qtyEq").value = qty;
  document.getElementById("btnSubmitEq").innerText = "Update";
  document.getElementById("btnCancelEq").classList.remove("d-none");
}

function resetEqForm() {
  document.getElementById("actEq").value = "create_eq";
  document.getElementById("idEq").value = "";
  document.getElementById("formEq").reset();
  document.getElementById("btnSubmitEq").innerText = "Save";
  document.getElementById("btnCancelEq").classList.add("d-none");
}

function manageLoan(txId, dueDate, status) {
  document.getElementById("mlTxId").value = txId;
  document.getElementById("mlDueDate").value = dueDate;
  document.getElementById("mlStatus").value = status;

  document.getElementById("mlDueDate").disabled = false;
  document.getElementById("mlStatus").disabled = false;
  document.getElementById("btnSubmitMl").disabled = false;

  document.getElementById("mlTitle").innerText = "Managing Tx #" + txId;
  document
    .getElementById("formManageLoan")
    .scrollIntoView({ behavior: "smooth", block: "center" });
}
