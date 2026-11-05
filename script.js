document.addEventListener("DOMContentLoaded", function () {
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
});
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

// Dynamic Stock Limiter
document.getElementById("eqSelect").addEventListener("change", function () {
  let maxStock = parseInt(
    this.options[this.selectedIndex].getAttribute("data-stock"),
  );
  let qtyInput = document.getElementById("checkoutQty");
  qtyInput.max = maxStock;
  if (parseInt(qtyInput.value) > maxStock) qtyInput.value = maxStock;
});

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

document.getElementById("newTxDate").min = new Date()
  .toISOString()
  .split("T")[0];
// Live Asynchronous Filtering Function
function filterBorrowers() {
  let searchText = document
    .getElementById("borrowerSearch")
    .value.toLowerCase();
  let selectedType = document
    .getElementById("borrowerTypeFilter")
    .value.toLowerCase();
  let rows = document.querySelectorAll("#borrowersTable tbody tr");

  rows.forEach((row) => {
    let idText = row.cells[0].innerText.toLowerCase();
    let nameText = row.cells[1].innerText.toLowerCase();
    let typeText = row.cells[2].innerText.toLowerCase();

    let matchesSearch =
      idText.includes(searchText) || nameText.includes(searchText);
    let matchesType = selectedType === "" || typeText.includes(selectedType);

    // Show row if it matches both search text and dropdown type, otherwise hide it
    if (matchesSearch && matchesType) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
}
function filterTransactions() {
  const searchQuery = document
    .getElementById("txClientSearch")
    .value.toLowerCase();
  const statusQuery = document
    .getElementById("txStatusFilter")
    .value.toLowerCase();
  const rows = document.querySelectorAll("#txClientTableBody tr");

  rows.forEach((row) => {
    const text = row.innerText.toLowerCase();
    const statusCell = row.cells[5] ? row.cells[5].innerText.toLowerCase() : ""; // assuming status is column 6

    const matchesSearch = text.includes(searchQuery);
    const matchesStatus = !statusQuery || statusCell.includes(statusQuery);

    row.style.display = matchesSearch && matchesStatus ? "" : "none";
  });
}

document
  .getElementById("txClientSearch")
  .addEventListener("input", filterTransactions);
document
  .getElementById("txStatusFilter")
  .addEventListener("change", filterTransactions);
document
  .getElementById("eqClientSearch")
  .addEventListener("input", function () {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll("#eqClientTableBody tr");

    rows.forEach((row) => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.includes(query) ? "" : "none";
    });
  });

document
  .getElementById("borrowerSearch")
  .addEventListener("keyup", filterBorrowers);
document
  .getElementById("borrowerTypeFilter")
  .addEventListener("change", filterBorrowers);

  document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("borrowerSearch");
    const typeFilter = document.getElementById("borrowerTypeFilter");
    const sortColumn = document.getElementById("borrowerSortColumn");
    const sortDirection = document.getElementById("borrowerSortDirection");
    const tableBody = document.querySelector("#borrowersTable tbody");

    function filterAndSortBorrowers() {
      const searchQuery = searchInput.value.toLowerCase().trim();
      const selectedType = typeFilter.value.toLowerCase();
      const colIndex = parseInt(sortColumn.value);
      const direction = sortDirection.value; 

      let rows = Array.from(tableBody.querySelectorAll("tr"));

      rows.forEach((row) => {
        const idText = row.cells[0].innerText.toLowerCase();
        const nameText = row.cells[1].innerText.toLowerCase();
        const typeText = row.cells[2].innerText.toLowerCase();

        const matchesSearch =
          idText.includes(searchQuery) || nameText.includes(searchQuery);
        const matchesType = !selectedType || typeText.includes(selectedType);

        row.style.display = matchesSearch && matchesType ? "" : "none";
      });
      rows.sort((rowA, rowB) => {
        const valA = rowA.cells[colIndex].innerText.trim().toLowerCase();
        const valB = rowB.cells[colIndex].innerText.trim().toLowerCase();

        let comparison = valA.localeCompare(valB, undefined, {
          numeric: true,
          sensitivity: "base",
        });

        return direction === "asc" ? comparison : -comparison;
      });

      rows.forEach((row) => tableBody.appendChild(row));
    }

    searchInput.addEventListener("input", filterAndSortBorrowers);
    typeFilter.addEventListener("change", filterAndSortBorrowers);
    sortColumn.addEventListener("change", filterAndSortBorrowers);
    sortDirection.addEventListener("change", filterAndSortBorrowers);
  });