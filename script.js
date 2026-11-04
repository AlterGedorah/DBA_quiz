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

// Attach event listeners for real-time updates
document
  .getElementById("borrowerSearch")
  .addEventListener("keyup", filterBorrowers);
document
  .getElementById("borrowerTypeFilter")
  .addEventListener("change", filterBorrowers);
