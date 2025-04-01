// Kurs Yönetim Sistemi JavaScript Fonksiyonları

document.addEventListener("DOMContentLoaded", function () {
  // Sidebar Toggle
  const sidebarToggle = document.getElementById("sidebarToggle");
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function (e) {
      e.preventDefault();
      document.body.classList.toggle("sb-sidenav-toggled");
      localStorage.setItem(
        "sb|sidebar-toggle",
        document.body.classList.contains("sb-sidenav-toggled")
      );
    });
  }

  // Önceki durumu geri yükle
  const sidebarState = localStorage.getItem("sb|sidebar-toggle");
  if (sidebarState === "true") {
    document.body.classList.add("sb-sidenav-toggled");
  }

  // Bildirim kapatma
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    const closeBtn = alert.querySelector(".btn-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        alert.remove();
      });
    }

    // 5 saniye sonra otomatik kapat
    setTimeout(function () {
      if (alert && alert.parentNode) {
        alert.classList.remove("show");
        setTimeout(function () {
          if (alert && alert.parentNode) {
            alert.remove();
          }
        }, 300);
      }
    }, 5000);
  });

  // DataTable Initialize (eğer varsa)
  if (typeof $.fn.DataTable !== "undefined") {
    $(".datatable").DataTable({
      language: {
        url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json",
      },
    });
  }

  // Tooltip Initialize (eğer varsa)
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  if (tooltipTriggerList.length > 0) {
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  // Yoklama işlemleri
  setupAttendanceForm();

  // Ödeme hesaplamaları
  setupPaymentCalculations();

  // Form doğrulama
  setupFormValidation();
});

/**
 * Yoklama formunu ayarlar
 */
function setupAttendanceForm() {
  const attendanceForm = document.getElementById("attendanceForm");
  if (!attendanceForm) return;

  const attendanceInputs = attendanceForm.querySelectorAll(
    'input[type="radio"]'
  );
  attendanceInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const row = this.closest("tr");
      const statusClass = this.value.toLowerCase();

      // Eski sınıfları temizle
      row.classList.remove(
        "table-success",
        "table-danger",
        "table-warning",
        "table-info"
      );

      // Yeni sınıfı ekle
      switch (this.value) {
        case "Var":
          row.classList.add("table-success");
          break;
        case "Yok":
          row.classList.add("table-danger");
          break;
        case "İzinli":
          row.classList.add("table-warning");
          break;
        case "Geç":
          row.classList.add("table-info");
          break;
      }
    });
  });
}

/**
 * Ödeme hesaplamalarını ayarlar
 */
function setupPaymentCalculations() {
  const paymentForm = document.getElementById("paymentForm");
  if (!paymentForm) return;

  const amountInput = document.getElementById("amount");
  const totalAmountDisplay = document.getElementById("totalAmount");

  // Miktar değiştiğinde toplam tutarı güncelle
  if (amountInput && totalAmountDisplay) {
    amountInput.addEventListener("input", function () {
      let amount = parseFloat(this.value) || 0;
      totalAmountDisplay.textContent = amount.toFixed(2) + " ₺";
    });
  }
}

/**
 * Form doğrulamayı ayarlar
 */
function setupFormValidation() {
  const forms = document.querySelectorAll(".needs-validation");
  if (forms.length === 0) return;

  // Bootstrap doğrulama
  Array.from(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }

        form.classList.add("was-validated");
      },
      false
    );
  });
}

/**
 * Tablo yazdırma işlevi
 * @param {string} tableId Yazdırılacak tablonun ID'si
 * @param {string} title Yazdırma başlığı
 */
function printTable(tableId, title) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const printWindow = window.open("", "_blank");

  printWindow.document.write(`
        <html>
        <head>
            <title>${title}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
            <style>
                body { padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                table, th, td { border: 1px solid #ddd; }
                th, td { padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; }
                .text-center { text-align: center; }
                .mt-3 { margin-top: 15px; }
                @media print {
                    .no-print { display: none; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <h2 class="text-center">${title}</h2>
            <div class="text-center">Tarih: ${new Date().toLocaleDateString(
              "tr-TR"
            )}</div>
            <hr>
            ${table.outerHTML}
            <div class="text-center mt-3">
                <p>Kurs Yönetim Sistemi - ${new Date().getFullYear()}</p>
            </div>
            <div class="text-center no-print mt-3">
                <button class="btn btn-primary" onclick="window.print()">Yazdır</button>
                <button class="btn btn-secondary" onclick="window.close()">Kapat</button>
            </div>
        </body>
        </html>
    `);

  printWindow.document.close();
}

/**
 * CSV dışa aktarma işlevi
 * @param {string} tableId Dışa aktarılacak tablonun ID'si
 * @param {string} filename Dosya adı
 */
function exportTableToCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  if (!table) return;

  let csv = [];
  let rows = table.querySelectorAll("tr");

  for (let i = 0; i < rows.length; i++) {
    let row = [],
      cols = rows[i].querySelectorAll("td, th");

    for (let j = 0; j < cols.length; j++) {
      // İçeriği al, HTML etiketlerini temizle
      let text = cols[j].innerText
        .replace(/(\r\n|\n|\r)/gm, "")
        .replace(/(\s\s)/gm, " ");
      // Tırnak içine al ve virgül varsa tırnak içinde göster
      row.push('"' + text + '"');
    }

    csv.push(row.join(","));
  }

  // Dosyayı indir
  downloadCSV(csv.join("\n"), filename);
}

/**
 * CSV dosyasını indirme işlevi
 * @param {string} csv CSV içeriği
 * @param {string} filename Dosya adı
 */
function downloadCSV(csv, filename) {
  let csvFile = new Blob(["\ufeff" + csv], { type: "text/csv;charset=utf-8;" });
  let downloadLink = document.createElement("a");

  downloadLink.download = filename;
  downloadLink.href = window.URL.createObjectURL(csvFile);
  downloadLink.style.display = "none";

  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
}

/**
 * Tüm öğrencilerin yoklama durumunu ayarlar
 * @param {string} status Ayarlanacak yoklama durumu (Var, Yok, İzinli, Geç)
 */
function setAllAttendance(status) {
  const form = document.getElementById("attendanceForm");
  if (!form) return;

  const radioButtons = form.querySelectorAll(`input[value="${status}"]`);
  radioButtons.forEach((radio) => {
    radio.checked = true;

    // Satır rengini güncelle
    const row = radio.closest("tr");
    if (row) {
      // Eski sınıfları temizle
      row.classList.remove(
        "table-success",
        "table-danger",
        "table-warning",
        "table-info"
      );

      // Yeni sınıfı ekle
      switch (status) {
        case "Var":
          row.classList.add("table-success");
          break;
        case "Yok":
          row.classList.add("table-danger");
          break;
        case "İzinli":
          row.classList.add("table-warning");
          break;
        case "Geç":
          row.classList.add("table-info");
          break;
      }
    }
  });
}
