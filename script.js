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

  // --- 2. BORROWER FORM & FILTER/SORT LOGIC ---
  const borrowerSearch = document.getElementById("borrowerSearch");
  const borrowerTypeFilter = document.getElementById("borrowerTypeFilter");
  const borrowerSortColumn = document.getElementById("borrowerSortColumn");
  const borrowerSortDirection = document.getElementById(
    "borrowerSortDirection",
  );
  const borrowersTableBody = document.querySelector("#borrowersTable tbody");

  function filterAndSortBorrowers() {
    if (!borrowersTableBody) return;
    const searchQuery = borrowerSearch
      ? borrowerSearch.value.toLowerCase().trim()
      : "";
    const selectedType = borrowerTypeFilter
      ? borrowerTypeFilter.value.toLowerCase()
      : "";
    const colIndex = borrowerSortColumn
      ? parseInt(borrowerSortColumn.value)
      : 1;
    const direction = borrowerSortDirection
      ? borrowerSortDirection.value
      : "asc";

    let rows = Array.from(borrowersTableBody.querySelectorAll("tr"));

    rows.forEach((row) => {
      if (row.cells.length < 3) return;
      const idText = row.cells[0].innerText.toLowerCase();
      const nameText = row.cells[1].innerText.toLowerCase();
      const typeText = row.cells[2].innerText.toLowerCase();

      const matchesSearch =
        idText.includes(searchQuery) || nameText.includes(searchQuery);
      const matchesType = !selectedType || typeText.includes(selectedType);

      row.style.display = matchesSearch && matchesType ? "" : "none";
    });

    rows.sort((rowA, rowB) => {
      if (rowA.cells.length <= colIndex || rowB.cells.length <= colIndex)
        return 0;
      const valA = rowA.cells[colIndex].innerText.trim().toLowerCase();
      const valB = rowB.cells[colIndex].innerText.trim().toLowerCase();

      let comparison = valA.localeCompare(valB, undefined, {
        numeric: true,
        sensitivity: "base",
      });

      return direction === "asc" ? comparison : -comparison;
    });

    rows.forEach((row) => borrowersTableBody.appendChild(row));
  }

  if (borrowerSearch)
    borrowerSearch.addEventListener("input", filterAndSortBorrowers);
  if (borrowerTypeFilter)
    borrowerTypeFilter.addEventListener("change", filterAndSortBorrowers);
  if (borrowerSortColumn)
    borrowerSortColumn.addEventListener("change", filterAndSortBorrowers);
  if (borrowerSortDirection)
    borrowerSortDirection.addEventListener("change", filterAndSortBorrowers);

  // --- 3. EQUIPMENT FILTER & SORT LOGIC ---
  const eqSearchInput = document.getElementById("eqSearchInput");
  const eqSortColumn = document.getElementById("eqSortColumn");
  const eqSortDirection = document.getElementById("eqSortDirection");
  const eqResetBtn = document.getElementById("eqResetBtn");
  const eqTableBody = document.getElementById("eqClientTableBody");

  function filterAndSortEquipment() {
    if (!eqTableBody) return;
    const query = eqSearchInput ? eqSearchInput.value.toLowerCase().trim() : "";
    const colIndex = eqSortColumn ? parseInt(eqSortColumn.value) : 1; // 0 = Prop #, 1 = Name, 2 = Qty
    const direction = eqSortDirection ? eqSortDirection.value : "asc";

    let rows = Array.from(eqTableBody.querySelectorAll("tr"));

    rows.forEach((row) => {
      if (row.cells.length < 4) return;
      const propNo = row.cells[0].innerText.toLowerCase();
      const eqName = row.cells[1].innerText.toLowerCase();

      const matchesSearch = propNo.includes(query) || eqName.includes(query);
      row.style.display = matchesSearch ? "" : "none";
    });

    rows.sort((rowA, rowB) => {
      if (rowA.cells.length < 4 || rowB.cells.length < 4) return 0;
      const valA = rowA.cells[colIndex].innerText.trim().toLowerCase();
      const valB = rowB.cells[colIndex].innerText.trim().toLowerCase();

      let comparison = 0;
      if (colIndex === 2) {
        const numA = parseFloat(valA) || 0;
        const numB = parseFloat(valB) || 0;
        comparison = numA - numB;
      } else {
        comparison = valA.localeCompare(valB, undefined, {
          numeric: true,
          sensitivity: "base",
        });
      }

      return direction === "asc" ? comparison : -comparison;
    });

    rows.forEach((row) => eqTableBody.appendChild(row));
  }

  if (eqSearchInput)
    eqSearchInput.addEventListener("input", filterAndSortEquipment);
  if (eqSortColumn)
    eqSortColumn.addEventListener("change", filterAndSortEquipment);
  if (eqSortDirection)
    eqSortDirection.addEventListener("change", filterAndSortEquipment);

  if (eqResetBtn) {
    eqResetBtn.addEventListener("click", function () {
      if (eqSearchInput) eqSearchInput.value = "";
      if (eqSortColumn) eqSortColumn.value = "1";
      if (eqSortDirection) eqSortDirection.value = "asc";
      filterAndSortEquipment();
    });
  }

  // --- 4. TRANSACTION FILTER LOGIC ---
  const txClientSearch = document.getElementById("txClientSearch");
  const txStatusFilter = document.getElementById("txStatusFilter");
  const txClientTableBody = document.getElementById("txClientTableBody");

  function filterTransactions() {
    if (!txClientTableBody) return;
    const searchQuery = txClientSearch
      ? txClientSearch.value.toLowerCase()
      : "";
    const statusQuery = txStatusFilter
      ? txStatusFilter.value.toLowerCase()
      : "";
    const rows = txClientTableBody.querySelectorAll("tr");

    rows.forEach((row) => {
      const text = row.innerText.toLowerCase();
      const statusCell = row.cells[5]
        ? row.cells[5].innerText.toLowerCase()
        : "";

      const matchesSearch = text.includes(searchQuery);
      const matchesStatus = !statusQuery || statusCell.includes(statusQuery);

      row.style.display = matchesSearch && matchesStatus ? "" : "none";
    });
  }

  if (txClientSearch)
    txClientSearch.addEventListener("input", filterTransactions);
  if (txStatusFilter)
    txStatusFilter.addEventListener("change", filterTransactions);
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
