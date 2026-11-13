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
