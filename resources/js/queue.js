const ticket = document.getElementById('ticket');

if (ticket) {
    const pageStyle = document.getElementById('page-size-style');
    const paperSelect = document.getElementById('paper-size');
    const printButton = document.getElementById('print-ticket');

    const pages = { '58mm': '58mm auto', '80mm': '80mm auto', a4: 'A4' };
    const margins = { '58mm': '0', '80mm': '0', a4: '12mm' };

    const applyPaper = (size) => {
        if (!pages[size]) {
            return;
        }

        ticket.className = ticket.className.replace(/\bticket--\S+/g, '').trim() + ' ticket--' + size;

        if (pageStyle) {
            pageStyle.textContent = `@page { size: ${pages[size]}; margin: ${margins[size]}; }`;
        }
    };

    if (paperSelect) {
        paperSelect.addEventListener('change', () => applyPaper(paperSelect.value));
    }

    if (printButton) {
        printButton.addEventListener('click', () => window.print());
    }
}
