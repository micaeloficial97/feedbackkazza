const params = new URLSearchParams(window.location.search);
const eventoPassado = (params.get("evento") || "indefinido").toLowerCase();

// API PHP hospedada no subdominio usado pelo frontend do Netlify.
const API_ENDPOINT =
  window.KAZZA_API_ENDPOINT ||
  "https://treinamentos.kazzapersianas.com.br/apifeedback/feedback.php";

document.querySelectorAll(".grupo-radios").forEach((grupo) => {
  const radios = grupo.querySelectorAll('input[type="radio"]');

  function pintar(valor) {
    grupo.querySelectorAll("label img").forEach((img) => {
      const match = img.getAttribute("src").match(/ativo(\d+)/i);
      if (!match) return;

      const numero = match[1];
      img.src = `ativo${numero}${numero === valor ? "cor" : "cnz"}.svg`;
    });
  }

  radios.forEach((radio) => {
    radio.addEventListener("change", (event) => pintar(event.target.value));
  });

  const marcado = grupo.querySelector('input[type="radio"]:checked');
  if (marcado) pintar(marcado.value);
});

const form = document.querySelector("#Feedback");
const statusMsg = document.querySelector("#status");

function setStatus(message, color) {
  if (!statusMsg) return;
  statusMsg.textContent = message;
  statusMsg.style.color = color;
}

function getCheckedValue(name) {
  const selected = document.querySelector(`input[name="${name}"]:checked`);
  return selected ? selected.value.trim() : "";
}

if (form) {
  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const resposta1 = Number.parseInt(getCheckedValue("avaliacao1"), 10);
    const resposta2 = Number.parseInt(getCheckedValue("avaliacao2"), 10);
    const resposta3 = Number.parseInt(getCheckedValue("avaliacao3"), 10);

    if (
      !Number.isInteger(resposta1) ||
      !Number.isInteger(resposta2) ||
      !Number.isInteger(resposta3)
    ) {
      setStatus("Selecione as 3 avaliacoes antes de enviar.", "red");
      return;
    }

    const payload = {
      nome: document.querySelector("#inputName").value.trim(),
      email: document.querySelector("#inputEmail").value.trim(),
      telefone: document.querySelector("#inputTel").value.trim(),
      resposta1,
      resposta2,
      resposta3,
      resposta4: document.querySelector("#inputSugestao").value.trim(),
      receber_novidades: document.querySelector("#novidades").checked,
      evento: eventoPassado,
    };

    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    setStatus("Enviando...", "black");

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000);

    try {
      const response = await fetch(API_ENDPOINT, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
        signal: controller.signal,
      });

      const contentType = response.headers.get("content-type") || "";
      let result = {};

      if (contentType.includes("application/json")) {
        result = await response.json();
      } else {
        const text = await response.text();
        result = { message: text };
      }

      if (!response.ok || result.ok === false) {
        throw new Error(result.message || `Falha no envio (HTTP ${response.status})`);
      }

      setStatus("Enviado com sucesso!", "rgb(0, 90, 126)");

      const qs = window.location.search || "";
      setTimeout(() => {
        window.location.replace("agradecimento.html" + qs);
      }, 1700);

      form.reset();
      document
        .querySelectorAll('.grupo-radios input[type="radio"]')
        .forEach((radio) => {
          radio.checked = false;
        });
      document.querySelectorAll(".grupo-radios").forEach((grupo) => {
        grupo.querySelectorAll("label img").forEach((img) => {
          const match = img.getAttribute("src").match(/ativo(\d+)/i);
          if (!match) return;
          img.src = `ativo${match[1]}cnz.svg`;
        });
      });
    } catch (error) {
      if (error.name === "AbortError") {
        setStatus("Tempo esgotado. Tente novamente.", "red");
      } else {
        setStatus(`Erro: ${error.message}`, "red");
      }
    } finally {
      clearTimeout(timeoutId);
      if (btn) btn.disabled = false;
    }
  });
}

const tel = document.querySelector("#inputTel");

const maskPhoneBR = (valor) => {
  const digits = valor.replace(/\D/g, "").slice(0, 11);
  if (digits.length > 10) {
    return digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, "($1) $2-$3");
  }

  return digits.replace(/^(\d{2})(\d{0,4})(\d{0,4}).*/, (_, ddd, parte1, parte2) => {
    if (!ddd) return "";
    return `(${ddd}) ${parte1}${parte2 ? `-${parte2}` : ""}`;
  });
};

if (tel) {
  tel.addEventListener("input", (event) => {
    event.target.value = maskPhoneBR(event.target.value);
  });
}
