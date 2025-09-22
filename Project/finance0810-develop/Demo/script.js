document.addEventListener('DOMContentLoaded', () => {
    // Calendar related elements
    const calendarDays = document.getElementById('calendarDays');
    const currentMonthYear = document.getElementById('currentMonthYear');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    let date = new Date();
    let currentYear = date.getFullYear();
    let currentMonth = date.getMonth();

    const months = [
        "一月", "二月", "三月", "四月", "五月", "六月",
        "七月", "八月", "九月", "十月", "十一月", "十二月"
    ];

    // Initial render of the calendar
    renderCalendar(currentYear, currentMonth);

    prevMonthBtn.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar(currentYear, currentMonth);
    });

    nextMonthBtn.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar(currentYear, currentMonth);
    });

    // Content switching logic
    const navLinks = document.querySelectorAll('.sidebar .navigation a');
    const contentSections = document.querySelectorAll('.content-section');

    let currentEditingCaseId = null; // To store the ID of the case currently being edited

    // Add click event listeners to sidebar navigation links
    navLinks.forEach(link => {
        console.log('Attaching event listener to link:', link.textContent.trim(), 'ID:', link.id);
        link.addEventListener('click', (event) => {
            console.log('Clicked event.target:', event.target); // Log the actual element clicked
            console.log('Clicked event.currentTarget:', event.currentTarget); // Log the element the listener is attached to
            event.preventDefault(); // Prevent default link behavior

            let targetSectionId = event.target.dataset.target; // Try to get target from data-target attribute
            let targetDisplayName = event.target.textContent.trim().split(' ')[0]; // Get display name from text content

            // If data-target is not set, use the old logic for main menu items
            if (!targetSectionId) {
                const linkText = event.target.textContent.trim();
                switch (true) {
                    case linkText.includes('儀錶板'):
                        targetSectionId = 'dashboard-content';
                        targetDisplayName = '儀錶板';
                        break;
                    case linkText.includes('聊天室'):
                        targetSectionId = 'chatroom-content';
                        targetDisplayName = '聊天室';
                        break;
                    case linkText.includes('業務管理'):
                        const parentLiBusiness = link.closest('li');
                        if (parentLiBusiness && parentLiBusiness.classList.contains('has-submenu')) {
                            parentLiBusiness.classList.toggle('active');
                        }
                        break;
                    case linkText.includes('網路進線管理'): // Handle new parent menu
                        const parentLiNetwork = link.closest('li');
                        if (parentLiNetwork && parentLiNetwork.classList.contains('has-submenu')) {
                            parentLiNetwork.classList.toggle('active');
                        }
                        break;
                    case linkText.includes('網路進線'):
                        targetSectionId = 'network-inbound-content';
                        targetDisplayName = '網路進線';
                        break;
                    case linkText.includes('送件管理'):
                        const parentLiSubmission = link.closest('li');
                        if (parentLiSubmission && parentLiSubmission.classList.contains('has-submenu')) {
                            parentLiSubmission.classList.toggle('active');
                        }
                        break;
                }
            }

            if (targetSectionId && targetDisplayName) {
                showContentSection(targetSectionId, targetDisplayName);
                // If the section contains a table, re-render it
                const targetTableId = targetSectionId.replace('-content', '-table');
                const targetTableBody = document.querySelector(`#${targetTableId} tbody`);
                if (targetTableBody) {
                    renderTable(casesData, targetTableId);
                }
                // If it's the tracking management section, render the calendar
                if (targetSectionId === 'tracking-management-content') {
                    renderCalendar(currentYear, currentMonth);
                }
            }
        });
    });

    function hideAllContentSections() {
        if (contentSections) { // Add null/empty check
            contentSections.forEach(section => {
                section.style.display = 'none';
            });
        }
    }

    function showContentSection(id, activeLinkText) {
        hideAllContentSections();
        const targetSection = document.getElementById(id);
        if (targetSection) {
            targetSection.style.display = 'block';
        }

        // Update active class for sidebar links
        if (navLinks) { // Add null/empty check
            navLinks.forEach(item => item.classList.remove('active'));
            const currentActiveLink = Array.from(navLinks).find(link => link.textContent.includes(activeLinkText));
            if (currentActiveLink) {
                currentActiveLink.classList.add('active');
            }
        }
    }

    // Define status mappings
    const statusMappings = {
        "unassigned": { displayName: "未指派", sectionId: "network-inbound-content" },
        "valid_customer": { displayName: "有效客", sectionId: "valid-customers-content" },
        "invalid_customer": { displayName: "無效客", sectionId: "invalid-customers-content" },
        "customer_service": { displayName: "客服", sectionId: "customer-service-content" },
        "blacklist": { displayName: "黑名單", sectionId: "blacklist-content" },
        "approved_disbursed": { displayName: "核准撥款", sectionId: "approved-disbursed-content" },
        "approved_undisbursed": { displayName: "核准未撥", sectionId: "approved-undisbursed-content" },
        "conditional_approval": { displayName: "附條件", sectionId: "conditional-approval-content" },
        "rejected": { displayName: "婉拒", sectionId: "rejected-content" },
        "tracking": { displayName: "追蹤管理", sectionId: "tracking-management-content" }
    };

    function updateBadgeCounts() {
        const counts = {
            "dashboard-count": 0,
            "chatroom-count": 0,
            "network-inbound-count": 0,
            "valid-customers-count": 0,
            "invalid-customers-count": 0,
            "customer-service-count": 0,
            "blacklist-count": 0,
            "approved-disbursed-count": 0,
            "approved-undisbursed-count": 0,
            "conditional-approval-count": 0,
            "rejected-count": 0,
            "tracking-management-count": 0,
            "tracking-record-count": 0 // Assuming tracking-record is a separate count
        };

        casesData.forEach(caseItem => {
            for (const statusValue in statusMappings) {
                if (caseItem.status === statusValue) {
                    const sectionId = statusMappings[statusValue].sectionId;
                    // Map sectionId to badgeId
                    switch (sectionId) {
                        case "network-inbound-content":
                            counts["network-inbound-count"]++;
                            break;
                        case "valid-customers-content":
                            counts["valid-customers-count"]++;
                            break;
                        case "invalid-customers-content":
                            counts["invalid-customers-count"]++;
                            break;
                        case "customer-service-content":
                            counts["customer-service-count"]++;
                            break;
                        case "blacklist-content":
                            counts["blacklist-count"]++;
                            break;
                        case "approved-disbursed-content":
                            counts["approved-disbursed-count"]++;
                            break;
                        case "approved-undisbursed-content":
                            counts["approved-undisbursed-count"]++;
                            break;
                        case "conditional-approval-content":
                            counts["conditional-approval-count"]++;
                            break;
                        case "rejected-content":
                            counts["rejected-count"]++;
                            break;
                        case "tracking-management-content":
                            counts["tracking-management-count"]++;
                            break;
                        // Add other badge updates as needed
                    }
                }
            }
        });

        for (const badgeId in counts) {
            const badgeElement = document.getElementById(badgeId);
            if (badgeElement) {
                badgeElement.textContent = counts[badgeId];
            }
        }
    }

    // Calendar rendering logic
    function renderCalendar(year, month) {
        currentMonthYear.textContent = `${year} 年 ${months[month]}`;
        calendarDays.innerHTML = '';

        const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 for Sunday, 1 for Monday...
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Fill in leading empty days
        for (let i = 0; i < firstDayOfMonth; i++) {
            const dayElement = document.createElement('div');
            dayElement.classList.add('calendar-day', 'empty-day');
            calendarDays.appendChild(dayElement);
        }

        // Fill in days of the month
        for (let i = 1; i <= daysInMonth; i++) {
            const dayElement = document.createElement('div');
            dayElement.classList.add('calendar-day');
            dayElement.textContent = i;
            dayElement.setAttribute('data-date', `${year}-${month + 1}-${i}`);
            // Removed event listener for showing daily contacts modal
            calendarDays.appendChild(dayElement);
        }
    }

    const casesData = [
        {
            id: "CASE250904012",
            status: "tracking",
            customerLevel: "A",
            contactSchedule: "2024-05-15", // Changed to a past date for testing overdue feature
            name: "王小明", // Added name field
            time: "2025-09-04 01:04", // Updated format
            assignee: "未指派",
            source: "網站表單",
            lineInfo: { lineDisplayName: "@ 1111", lineAddFriendId: "" }, // Updated to object
            consultationItem: "二胎房貸",
            website: "房貸先生<br>easypay-life.com.tw",
            contactInfo: { contactEmail: "", contactPhone: "0911111111" }, // Updated to object
            location: "臺北市",
            demandAmount: "30萬以下",
            customField: "無自定義欄位",
            operation: "<span class=\"action-icons\">◎ →</span>",
            hasBeenContacted: false, // New field
            pendingSubmission: false, // New field
            // 詳細欄位: 個人資料
            birthDate: "",
            idNumber: "",
            education: "",
            // 詳細欄位: 聯絡資訊
            regionAddress: "",
            homeAddress: "",
            landlinePhone: "",
            commAddressSameAsHome: false,
            commAddress: "",
            commPhone: "",
            residenceDuration: "",
            residenceOwner: "",
            telecomOperator: "",
            // 詳細欄位: 公司資料
            email: "",
            companyName: "",
            companyPhone: "",
            companyAddress: "",
            jobTitle: "",
            monthlyIncome: "",
            hasLaborInsurance: false,
            companyTenure: "",
            // 詳細欄位: 緊急聯絡人
            emergencyContact1Name: "",
            emergencyContact1Relationship: "",
            emergencyContact1Phone: "",
            contactTime1: "",
            confidential1: false,
            emergencyContact2Name: "",
            emergencyContact2Relationship: "",
            emergencyContact2Phone: "",
            contactTime2: "",
            confidential2: false,
            referrer: ""
        },
        {
            id: "CASE250902011",
            status: "unassigned",
            customerLevel: "A",
            contactSchedule: "2025-09-07",
            name: "李大華", // Added name field
            time: "2025-09-02 12:31", // Updated format
            assignee: "未指派",
            source: "網站表單",
            lineInfo: { lineDisplayName: "@ 123", lineAddFriendId: "" }, // Updated to object
            consultationItem: "二胎房貸",
            website: "房貸先生<br>easypay-life.com.tw",
            contactInfo: { contactEmail: "", contactPhone: "0912345678" }, // Updated to object
            location: "臺北市<br>測試地址",
            demandAmount: "200-300萬",
            customField: "無自定義欄位",
            operation: "<span class=\"action-icons\">◎ →</span>",
            hasBeenContacted: false, // New field
            pendingSubmission: false, // New field
            // 詳細欄位: 個人資料
            birthDate: "",
            idNumber: "",
            education: "",
            // 詳細欄位: 聯絡資訊
            regionAddress: "",
            homeAddress: "",
            landlinePhone: "",
            commAddressSameAsHome: false,
            commAddress: "",
            commPhone: "",
            residenceDuration: "",
            residenceOwner: "",
            telecomOperator: "",
            // 詳細欄位: 公司資料
            email: "",
            companyName: "",
            companyPhone: "",
            companyAddress: "",
            jobTitle: "",
            monthlyIncome: "",
            hasLaborInsurance: false,
            companyTenure: "",
            // 詳細欄位: 緊急聯絡人
            emergencyContact1Name: "",
            emergencyContact1Relationship: "",
            emergencyContact1Phone: "",
            contactTime1: "",
            confidential1: false,
            emergencyContact2Name: "",
            emergencyContact2Relationship: "",
            emergencyContact2Phone: "",
            contactTime2: "",
            confidential2: false,
            referrer: ""
        },
        {
            id: "CASE250902010",
            status: "unassigned",
            customerLevel: "A",
            contactSchedule: "2025-09-10",
            name: "張美麗", // Added name field
            time: "2025-09-02 12:28", // Updated format
            assignee: "未指派",
            source: "網站表單",
            lineInfo: { lineDisplayName: "@zx456", lineAddFriendId: "" }, // Updated to object
            consultationItem: "汽機車貸",
            website: "房貸先生<br>easypay-life.com.tw",
            contactInfo: { contactEmail: "qw12345678@gmail.com", contactPhone: "0912345678" }, // Updated to object
            location: "臺北市<br>測試",
            demandAmount: "100-200萬",
            customField: "無自定義欄位",
            operation: "<span class=\"action-icons\">◎ →</span>",
            hasBeenContacted: false, // New field
            pendingSubmission: false, // New field
            // 詳細欄位: 個人資料
            birthDate: "",
            idNumber: "",
            education: "",
            // 詳細欄位: 聯絡資訊
            regionAddress: "",
            homeAddress: "",
            landlinePhone: "",
            commAddressSameAsHome: false,
            commAddress: "",
            commPhone: "",
            residenceDuration: "",
            residenceOwner: "",
            telecomOperator: "",
            // 詳細欄位: 公司資料
            email: "",
            companyName: "",
            companyPhone: "",
            companyAddress: "",
            jobTitle: "",
            monthlyIncome: "",
            hasLaborInsurance: false,
            companyTenure: "",
            // 詳細欄位: 緊急聯絡人
            emergencyContact1Name: "",
            emergencyContact1Relationship: "",
            emergencyContact1Phone: "",
            contactTime1: "",
            confidential1: false,
            emergencyContact2Name: "",
            emergencyContact2Relationship: "",
            emergencyContact2Phone: "",
            contactTime2: "",
            confidential2: false,
            referrer: ""
        },
        {
            id: "CASE250901009",
            status: "unassigned",
            customerLevel: "A",
            contactSchedule: "2025-09-12",
            name: "林志玲", // Added name field
            time: "2025-09-01 12:23", // Updated format
            assignee: "未指派",
            source: "網站表單",
            lineInfo: { lineDisplayName: "@ 1111", lineAddFriendId: "" }, // Updated to object
            consultationItem: "二胎房貸",
            website: "房貸先生<br>easypay-life.com.tw",
            contactInfo: { contactEmail: "", contactPhone: "0911111111" }, // Updated to object
            location: "臺北市",
            demandAmount: "30萬以下",
            customField: "無自定義欄位",
            operation: "<span class=\"action-icons\">◎ →</span>",
            hasBeenContacted: false, // New field
            pendingSubmission: false, // New field
            // 詳細欄位: 個人資料
            birthDate: "",
            idNumber: "",
            education: "",
            // 詳細欄位: 聯絡資訊
            regionAddress: "",
            homeAddress: "",
            landlinePhone: "",
            commAddressSameAsHome: false,
            commAddress: "",
            commPhone: "",
            residenceDuration: "",
            residenceOwner: "",
            telecomOperator: "",
            // 詳細欄位: 公司資料
            email: "",
            companyName: "",
            companyPhone: "",
            companyAddress: "",
            jobTitle: "",
            monthlyIncome: "",
            hasLaborInsurance: false,
            companyTenure: "",
            // 詳細欄位: 緊急聯絡人
            emergencyContact1Name: "",
            emergencyContact1Relationship: "",
            emergencyContact1Phone: "",
            contactTime1: "",
            confidential1: false,
            emergencyContact2Name: "",
            emergencyContact2Relationship: "",
            emergencyContact2Phone: "",
            contactTime2: "",
            confidential2: false,
            referrer: ""
        },
        {
            id: "CASE250831008",
            status: "unassigned",
            customerLevel: "A",
            contactSchedule: "2025-09-11",
            name: "陳冠宇", // Added name field
            time: "2025-08-31 23:31", // Updated format
            assignee: "未指派",
            source: "網站表單",
            lineInfo: { lineDisplayName: "@zx456", lineAddFriendId: "" }, // Updated to object
            consultationItem: "汽機車貸",
            website: "房貸先生<br>easypay-life.com.tw",
            contactInfo: { contactEmail: "as12345678@gmail.com", contactPhone: "0912345678" }, // Updated to object
            location: "臺北市<br>測試地址",
            demandAmount: "30萬以下",
            customField: "無自定義欄位",
            operation: "<span class=\"action-icons\">◎ →</span>",
            hasBeenContacted: false, // New field
            pendingSubmission: false, // New field
            // 詳細欄位: 個人資料
            birthDate: "",
            idNumber: "",
            education: "",
            // 詳細欄位: 聯絡資訊
            regionAddress: "",
            homeAddress: "",
            landlinePhone: "",
            commAddressSameAsHome: false,
            commAddress: "",
            commPhone: "",
            residenceDuration: "",
            residenceOwner: "",
            telecomOperator: "",
            // 詳細欄位: 公司資料
            email: "",
            companyName: "",
            companyPhone: "",
            companyAddress: "",
            jobTitle: "",
            monthlyIncome: "",
            hasLaborInsurance: false,
            companyTenure: "",
            // 詳細欄位: 緊急聯絡人
            emergencyContact1Name: "",
            emergencyContact1Relationship: "",
            emergencyContact1Phone: "",
            contactTime1: "",
            confidential1: false,
            emergencyContact2Name: "",
            emergencyContact2Relationship: "",
            emergencyContact2Phone: "",
            contactTime2: "",
            confidential2: false,
            referrer: ""
        }
    ];

    function renderTable(cases, targetTableId) {
        console.log('renderTable called for targetTableId:', targetTableId); // Debug log
        const tableBody = document.querySelector(`#${targetTableId} tbody`);
        if (!tableBody) return;

        tableBody.innerHTML = ''; // Clear existing rows

        const filteredCases = cases.filter(caseItem => {
            // Filter cases based on the targetTableId and statusMappings
            const currentSectionId = targetTableId.replace('-table', '-content');
            for (const statusValue in statusMappings) {
                if (statusMappings[statusValue].sectionId === currentSectionId) {
                    return caseItem.status === statusValue;
                }
            }
            return false; // If no mapping, don't show the case
        });

        filteredCases.forEach(caseItem => {
            const row = tableBody.insertRow();
            row.setAttribute('data-case-id', caseItem.id); // Add data-case-id for easy lookup

            // Handle Customer Level dropdown for tracking-management-table
            let customerLevelCell = null;
            if (targetTableId === 'tracking-management-table') {
                customerLevelCell = row.insertCell(); // Insert the customer level cell first
                customerLevelCell.innerHTML = `
                    <select class="customer-level-select" data-case-id="${caseItem.id}">
                        <option value="A" ${caseItem.customerLevel === 'A' ? 'selected' : ''}>A</option>
                        <option value="B" ${caseItem.customerLevel === 'B' ? 'selected' : ''}>B</option>
                        <option value="C" ${caseItem.customerLevel === 'C' ? 'selected' : ''}>C</option>
                    </select>
                `;
                // Add event listener for customer level change (for tracking-management-table only)
                const selectElement = customerLevelCell.querySelector('.customer-level-select');
                if (selectElement) {
                    selectElement.addEventListener('change', (event) => {
                        console.log(`Case ${caseItem.id} customer level changed to: ${event.target.value}`);
                        // For demo purposes, we'll just log it
                    });
                }
            }

            // Determine the index for the '案件狀態' column
            const statusColIndex = (targetTableId === 'tracking-management-table') ? 1 : 0; // If customer level is present, status is at index 1

            // Render common cells
            const cells = [];
            // Add customer level to cells if it's tracking-management-table
            if (targetTableId === 'tracking-management-table') {
                cells.push(caseItem.customerLevel || ''); // Placeholder for customer level
            }

            cells.push(
                caseItem.status || '', // This will be handled specifically for dropdown
                caseItem.id || '', // hidden-column
                caseItem.time || '',
                caseItem.assignee || '',
                caseItem.source || '',
                caseItem.name || '', // Inserted Name
                (caseItem.lineInfo?.lineDisplayName || '') + (caseItem.lineInfo?.lineAddFriendId ? ` (${caseItem.lineInfo.lineAddFriendId})` : '') || '',
                caseItem.consultationItem || '',
                caseItem.website || '',
                (caseItem.contactInfo?.contactEmail || '') + (caseItem.contactInfo?.contactPhone ? `<br>${caseItem.contactInfo.contactPhone}` : '') || '',
                caseItem.location || '', // hidden-column
                caseItem.demandAmount || '', // hidden-column
                caseItem.customField || '', // hidden-column
                caseItem.operation || ''
            );

            // Adjust index for cells.forEach to account for customerLevelCell
            let currentCellIndex = 0;
            cells.forEach((text, index) => {
                let cell;
                // Skip the customerLevelCell if it was already inserted
                if (targetTableId === 'tracking-management-table' && index === 0) {
                    cell = customerLevelCell;
                } else {
                    cell = row.insertCell();
                }

                if (currentCellIndex === statusColIndex) { // This is the '案件狀態' column
                    let statusDropdownHtml = `<select class="case-status-select" data-case-id="${caseItem.id}">`;
                    for (const statusValue in statusMappings) {
                        const mapping = statusMappings[statusValue];
                        const selected = (caseItem.status === statusValue) ? 'selected' : '';
                        statusDropdownHtml += `<option value="${statusValue}" ${selected}>${mapping.displayName}</option>`;
                    }
                    statusDropdownHtml += `</select>`;
                    cell.innerHTML = statusDropdownHtml;

                    cell.querySelector('.case-status-select').addEventListener('change', (event) => {
                        console.log('Change event triggered for case:', caseItem.id); // Debug log
                        const newStatus = event.target.value;
                        const targetMapping = statusMappings[newStatus];
                        
                        console.log('New Status:', newStatus, 'Target Section ID:', targetMapping.sectionId, 'Target Display Name:', targetMapping.displayName); // Debug log
                        showContentSection(targetMapping.sectionId, targetMapping.displayName); // Switch the active section

                        // Update the actual caseItem.status in casesData
                        const caseToUpdate = casesData.find(c => c.id === caseItem.id);
                        if (caseToUpdate) {
                            caseToUpdate.status = newStatus;
                            console.log('Case status updated in casesData:', caseToUpdate); // Debug log
                        }

                        console.log('Before re-rendering target table for:', targetMapping.sectionId); // Debug log
                        renderTable(casesData, targetMapping.sectionId); // Re-render the target table to reflect the change
                        console.log('After re-rendering target table for:', targetMapping.sectionId); // Debug log

                        updateBadgeCounts(); // Update counts after status change
                    });

                } else {
                    cell.innerHTML = text; // Original rendering for other cells
                }
                // Add hidden-column class for specific columns
                if (targetTableId === 'network-inbound-table' && (index === 1 || index === 10 || index === 11 || index === 12)) {
                    cell.classList.add('hidden-column');
                }
                if ((targetTableId === 'valid-customers-table' || targetTableId === 'invalid-customers-table' ||
                     targetTableId === 'customer-service-table' || targetTableId === 'blacklist-table' ||
                     targetTableId === 'approved-disbursed-table' || targetTableId === 'approved-undisbursed-table' ||
                     targetTableId === 'conditional-approval-table' || targetTableId === 'rejected-table') &&
                    (index === 1 || index === 10 || index === 11 || index === 12)) {
                    cell.classList.add('hidden-column');
                }
                if (targetTableId === 'tracking-management-table' && (index === 2 || index === 11 || index === 12)) {
                    cell.classList.add('hidden-column');
                }
                currentCellIndex++; // Increment for next cell
            });

            // Add operation buttons for tracking-management-table
            if (targetTableId === 'tracking-management-table') {
                const operationCell = row.cells[row.cells.length - 1]; // Get the last cell (Operation)

                if (caseItem.pendingSubmission) {
                    operationCell.innerHTML = `
                        <div style="background-color: #f8d7da; color: #721c24; padding: 5px; border-radius: 4px; display: inline-block; margin-right: 5px;">待送件</div>
                        <button class="action-button cancel-submission-button" data-case-id="${caseItem.id}" style="background-color: #dc3545; color: white; margin-right: 5px;">取消</button>
                        <button class="action-button edit-case-button" data-case-id="${caseItem.id}">編輯</button>
                    `;
                    operationCell.querySelector('.cancel-submission-button').addEventListener('click', (event) => {
                        const caseId = event.target.dataset.caseId;
                        const caseToCancel = casesData.find(c => c.id === caseId);
                        if (caseToCancel) {
                            caseToCancel.pendingSubmission = false; // Set pending submission to false
                            console.log(`Case ${caseId} submission cancelled.`);
                            renderTable(casesData, targetTableId); // Re-render the current table to update UI
                        }
                    });
                } else {
                    operationCell.innerHTML = `
                        <button class="action-button submit-case-button" data-case-id="${caseItem.id}">進件</button>
                        <button class="action-button schedule-tracking-button" data-case-id="${caseItem.id}">安排追蹤</button>
                        <button class="action-button edit-case-button" data-case-id="${caseItem.id}">編輯</button>
                    `;

                    // Event listener for "進件" button
                    operationCell.querySelector('.submit-case-button').addEventListener('click', (event) => {
                        const clickedButton = event.target;
                        const caseId = clickedButton.dataset.caseId;
                        const caseToSubmit = casesData.find(c => c.id === caseId);

                        if (caseToSubmit) {
                            caseToSubmit.pendingSubmission = true; // Mark as pending submission
                            console.log(`Case ${caseId} marked as pending submission.`);
                            renderTable(casesData, targetTableId); // Re-render the current table to update UI
                        }
                    });

                    // Event listener for "安排追蹤" button
                    operationCell.querySelector('.schedule-tracking-button').addEventListener('click', (event) => {
                        const caseId = event.target.dataset.caseId;
                        console.log(`安排追蹤 clicked for case: ${caseId}. This would open a calendar/modal to set a new tracking date.`);
                        // Future implementation: Open calendar/modal to set tracking date
                    });
                }
            } else { // For other tables (network-inbound, etc.)
                const operationCell = row.cells[row.cells.length - 1];
                operationCell.innerHTML = `
                    <button class="action-button edit-case-button" data-case-id="${caseItem.id}">編輯</button>
                `;
            }
        });
    }
    
    // Event delegation for edit buttons to handle dynamically created buttons
    document.body.addEventListener('click', (event) => {
        if (event.target.classList.contains('edit-case-button')) {
            const caseId = event.target.dataset.caseId;
            showEditCaseForm(caseId);
        }
    });

    // Function to show the edit case form in a new section
    function showEditCaseForm(caseId) {
        const editCaseSection = document.getElementById('edit-case-section');
        const editCaseIdDisplay = document.getElementById('edit-case-id-display');
        const editCaseFormFields = document.getElementById('edit-case-form-fields');
        const saveCaseButton = document.getElementById('save-case-button');
        const cancelEditButton = document.getElementById('cancel-edit-button');

        const caseToEdit = casesData.find(c => c.id === caseId);
        if (caseToEdit) {
            currentEditingCaseId = caseId;
            editCaseIdDisplay.textContent = caseId;
            renderEditFormFields(caseToEdit);
            
            // Hide all other content sections and show the edit section
            hideAllContentSections();
            editCaseSection.style.display = 'block';

            // Re-attach event listeners for save and cancel buttons
            // Remove existing listeners first to prevent duplicates
            if (saveCaseButton) {
                saveCaseButton.removeEventListener('click', handleSaveClick);
                saveCaseButton.addEventListener('click', handleSaveClick);
            }
            if (cancelEditButton) {
                cancelEditButton.removeEventListener('click', handleCancelClick);
                cancelEditButton.addEventListener('click', handleCancelClick);
            }

        } else {
            console.error('Case not found for editing:', caseId);
        }
    }

    // Event handler for save button
    function handleSaveClick() {
        if (currentEditingCaseId) {
            saveEditedCase(currentEditingCaseId);
            // After saving, navigate back to the previous section
            const caseToUpdate = casesData.find(c => c.id === currentEditingCaseId);
            if (caseToUpdate) {
                const targetSectionId = statusMappings[caseToUpdate.status]?.sectionId;
                if (targetSectionId) {
                    showContentSection(targetSectionId, statusMappings[caseToUpdate.status].displayName);
                    renderTable(casesData, targetSectionId.replace('-content', '-table'));
                }
            }
        }
    }

    // Event handler for cancel button
    function handleCancelClick() {
        // Just navigate back to the previous section without saving
        const previousCase = casesData.find(c => c.id === currentEditingCaseId);
        if (previousCase) {
            const targetSectionId = statusMappings[previousCase.status]?.sectionId;
            if (targetSectionId) {
                showContentSection(targetSectionId, statusMappings[previousCase.status].displayName);
                renderTable(casesData, targetSectionId.replace('-content', '-table'));
            }
        } else {
             // Fallback if currentEditingCaseId is somehow lost, go to network-inbound
            showContentSection('network-inbound-content', '網路進線');
            renderTable(casesData, 'network-inbound-table');
        }
        currentEditingCaseId = null; // Clear editing state
    }

    // Function to render all form fields in the modal
    function renderEditFormFields(caseData) {
        const editCaseFormFields = document.getElementById('edit-case-form-fields');
        editCaseFormFields.innerHTML = ''; // Clear previous fields

        const fieldsConfig = [
                    // 顯示欄位
                    { key: 'status', label: '案件狀態', type: 'select', options: Object.keys(statusMappings).map(key => ({ value: key, text: statusMappings[key].displayName })) },
                    { key: 'time', label: '時間', type: 'text' },
                    { key: 'assignee', label: '承辦業務', type: 'text' },
                    { key: 'source', label: '來源管道', type: 'text' },
                    { key: 'name', label: '姓名', type: 'text' },
                    { 
                        key: 'lineInfo', 
                        label: 'LINE資訊', 
                        type: 'custom-line-info', 
                        subFields: [
                            { key: 'lineDisplayName', label: '顯示名稱', type: 'text' },
                            { key: 'lineAddFriendId', label: '加好友ID', type: 'text' }
                        ]
                    },
                    { key: 'consultationItem', label: '諮詢項目', type: 'text' },
                    { key: 'website', label: '網站', type: 'text' },
                    { 
                        key: 'contactInfo', 
                        label: '聯絡資訊', 
                        type: 'custom-contact-info', 
                        subFields: [
                            { key: 'contactEmail', label: '電子郵件', type: 'email' },
                            { key: 'contactPhone', label: '手機', type: 'text' }
                        ]
                    },

                    // 詳細欄位: 個人資料
                    { key: 'birthDate', label: '出生年月日', type: 'date', group: '個人資料' },
                    { key: 'idNumber', label: '身份證字號', type: 'text', group: '個人資料' },
                    { key: 'education', label: '最高學歷', type: 'text', group: '個人資料' },

                    // 詳細欄位: 聯絡資訊
                    { key: 'regionAddress', label: '所在地區', type: 'text', group: '聯絡資訊' },
                    { key: 'homeAddress', label: '戶籍地址', type: 'text', group: '聯絡資訊' },
                    { key: 'landlinePhone', label: '室內電話', type: 'text', group: '聯絡資訊' },
                    { key: 'commAddressSameAsHome', label: '通訊地址是否同戶籍地', type: 'checkbox', group: '聯絡資訊' },
                    { key: 'commAddress', label: '通訊地址', type: 'text', group: '聯絡資訊' },
                    { key: 'commPhone', label: '通訊電話', type: 'text', group: '聯絡資訊' },
                    { key: 'residenceDuration', label: '現居地住多久', type: 'text', group: '聯絡資訊' },
                    { key: 'residenceOwner', label: '居住地持有人', type: 'text', group: '聯絡資訊' },
                    { key: 'telecomOperator', label: '電信業者', type: 'text', group: '聯絡資訊' },

                    // 公司資料
                    { key: 'email', label: '電子郵件', type: 'email', group: '公司資料' },
                    { key: 'companyName', label: '公司名稱', type: 'text', group: '公司資料' },
                    { key: 'companyPhone', label: '公司電話', type: 'text', group: '公司資料' },
                    { key: 'companyAddress', label: '公司地址', type: 'text', group: '公司資料' },
                    { key: 'jobTitle', label: '職稱', type: 'text', group: '公司資料' },
                    { key: 'monthlyIncome', label: '月收入', type: 'text', group: '公司資料' },
                    { key: 'hasLaborInsurance', label: '有無新轉勞保', type: 'checkbox', group: '公司資料' },
                    { key: 'companyTenure', label: '目前公司在職多久', type: 'text', group: '公司資料' },

                    // 其他資訊
                    { key: 'id', label: '案件編號', type: 'text', group: '其他資訊', readonly: true }, // ID is caseId
                    { key: 'demandAmount', label: '需求金額', type: 'text', group: '其他資訊' },
                    { key: 'customField', label: '自定義欄位', type: 'textarea', group: '其他資訊' },

                    // 緊急聯絡人
                    { key: 'emergencyContact1Name', label: '聯絡人①姓名', type: 'text', group: '緊急聯絡人' },
                    { key: 'emergencyContact1Relationship', label: '聯絡人①關係', type: 'text', group: '緊急聯絡人' },
                    { key: 'emergencyContact1Phone', label: '聯絡人①電話', type: 'text', group: '緊急聯絡人' },
                    { key: 'contactTime1', label: '方便聯絡時間', type: 'text', group: '緊急聯絡人' },
                    { key: 'confidential1', label: '是否保密', type: 'checkbox', group: '緊急聯絡人' },
                    { key: 'emergencyContact2Name', label: '聯絡人②姓名', type: 'text', group: '緊急聯絡人' },
                    { key: 'emergencyContact2Relationship', label: '聯絡人②關係', type: 'text', group: '緊急聯絡人' },
                    { key: 'emergencyContact2Phone', label: '聯絡人②電話', type: 'text', group: '緊急聯絡人' },
                    { key: 'contactTime2', label: '方便聯絡時間', type: 'text', group: '緊急聯絡人' },
                    { key: 'confidential2', label: '是否保密', type: 'checkbox', group: '緊急聯絡人' },
                    { key: 'referrer', label: '介紹人', type: 'text', group: '緊急聯絡人' }
                ];

        const groupedFields = fieldsConfig.reduce((acc, field) => {
            const groupName = field.group || '基本資訊';
            if (!acc[groupName]) {
                acc[groupName] = [];
            }
            acc[groupName].push(field);
            return acc;
        }, {});

        for (const groupName in groupedFields) {
            const groupDiv = document.createElement('div');
            groupDiv.classList.add('form-group-section');
            if (groupName !== '基本資訊') {
                const groupTitle = document.createElement('h3');
                groupTitle.textContent = groupName;
                groupDiv.appendChild(groupTitle);
            }

            groupedFields[groupName].forEach(field => {
                const fieldDiv = document.createElement('div');
                fieldDiv.classList.add('form-field');

                const label = document.createElement('label');
                label.textContent = field.label + '：';
                label.setAttribute('for', field.key);
                fieldDiv.appendChild(label);

                let inputElement;
                if (field.type === 'select') {
                    inputElement = document.createElement('select');
                    field.options.forEach(option => {
                        const opt = document.createElement('option');
                        opt.value = option.value;
                        opt.textContent = option.text;
                        if (caseData[field.key] === option.value) {
                            opt.selected = true;
                        }
                        inputElement.appendChild(opt);
                    });
                } else if (field.type === 'custom-line-info') {
                    const subFieldsContainer = document.createElement('div');
                    subFieldsContainer.classList.add('custom-subfields-container');
                    
                    const lineDisplayName = caseData[field.key]?.lineDisplayName || '';
                    const lineAddFriendId = caseData[field.key]?.lineAddFriendId || '';

                    field.subFields.forEach(subField => {
                        const subFieldDiv = document.createElement('div');
                        subFieldDiv.classList.add('form-field'); // Use form-field class for subfields

                        const subLabel = document.createElement('label');
                        subLabel.textContent = subField.label + '：';
                        subLabel.setAttribute('for', subField.key + caseData.id); // Unique ID for subfield
                        subFieldDiv.appendChild(subLabel);

                        const subInputElement = document.createElement('input');
                        subInputElement.type = subField.type;
                        subInputElement.id = subField.key + caseData.id; // Unique ID for subfield
                        subInputElement.name = subField.key;
                        // Set value based on whether it's display name or add friend ID
                        if (subField.key === 'lineDisplayName') {
                            subInputElement.value = lineDisplayName;
                        } else if (subField.key === 'lineAddFriendId') {
                            subInputElement.value = lineAddFriendId;
                        }
                        subFieldDiv.appendChild(subInputElement);
                        subFieldsContainer.appendChild(subFieldDiv);
                    });
                    inputElement = subFieldsContainer;
                } else if (field.type === 'custom-contact-info') {
                    const subFieldsContainer = document.createElement('div');
                    subFieldsContainer.classList.add('custom-subfields-container');

                    const contactEmail = caseData[field.key]?.contactEmail || '';
                    const contactPhone = caseData[field.key]?.contactPhone || '';

                    field.subFields.forEach(subField => {
                        const subFieldDiv = document.createElement('div');
                        subFieldDiv.classList.add('form-field'); // Use form-field class for subfields

                        const subLabel = document.createElement('label');
                        subLabel.textContent = subField.label + '：';
                        subLabel.setAttribute('for', subField.key + caseData.id); // Unique ID for subfield
                        subFieldDiv.appendChild(subLabel);

                        const subInputElement = document.createElement('input');
                        subInputElement.type = subField.type;
                        subInputElement.id = subField.key + caseData.id; // Unique ID for subfield
                        subInputElement.name = subField.key;
                        // Set value based on whether it's email or phone
                        if (subField.key === 'contactEmail') {
                            subInputElement.value = contactEmail;
                        } else if (subField.key === 'contactPhone') {
                            subInputElement.value = contactPhone;
                        }
                        subFieldDiv.appendChild(subInputElement);
                        subFieldsContainer.appendChild(subFieldDiv);
                    });
                    inputElement = subFieldsContainer;
                } else if (field.type === 'textarea') {
                    inputElement = document.createElement('textarea');
                    inputElement.value = caseData[field.key] || '';
                } else if (field.type === 'checkbox') {
                    inputElement = document.createElement('input');
                    inputElement.type = 'checkbox';
                    inputElement.checked = caseData[field.key] || false;
                } else {
                    inputElement = document.createElement('input');
                    inputElement.type = field.type;
                    inputElement.value = caseData[field.key] || '';
                    if (field.readonly) {
                        inputElement.readOnly = true;
                    }
                }
                inputElement.id = field.key;
                inputElement.name = field.key;
                fieldDiv.appendChild(inputElement);

                groupDiv.appendChild(fieldDiv);
            });
            editCaseFormFields.appendChild(groupDiv);
        }
    }

    // Function to save edited case data
    function saveEditedCase(caseId) {
        const caseToUpdate = casesData.find(c => c.id === caseId);
        if (!caseToUpdate) {
            console.error('Case not found for saving:', caseId);
            return;
        }

        const oldStatus = caseToUpdate.status; // Store old status for comparison

        // Iterate through the fieldsConfig to get updated values
        const fieldsConfig = [
            // 顯示欄位
            { key: 'status', label: '案件狀態', type: 'select', options: Object.keys(statusMappings).map(key => ({ value: key, text: statusMappings[key].displayName })) },
            { key: 'time', label: '時間', type: 'text' },
            { key: 'assignee', label: '承辦業務', type: 'text' },
            { key: 'source', label: '來源管道', type: 'text' },
            { key: 'name', label: '姓名', type: 'text' },
            { 
                key: 'lineInfo', 
                label: 'LINE資訊', 
                type: 'custom-line-info', 
                subFields: [
                    { key: 'lineDisplayName', label: '顯示名稱', type: 'text' },
                    { key: 'lineAddFriendId', label: '加好友ID', type: 'text' }
                ]
            },
            { key: 'consultationItem', label: '諮詢項目', type: 'text' },
            { key: 'website', label: '網站', type: 'text' },
            { 
                key: 'contactInfo', 
                label: '聯絡資訊', 
                type: 'custom-contact-info', 
                subFields: [
                    { key: 'contactEmail', label: '電子郵件', type: 'email' },
                    { key: 'contactPhone', label: '手機', type: 'text' }
                ]
            },

            // 詳細欄位: 個人資料
            { key: 'birthDate', label: '出生年月日', type: 'date', group: '個人資料' },
            { key: 'idNumber', label: '身份證字號', type: 'text', group: '個人資料' },
            { key: 'education', label: '最高學歷', type: 'text', group: '個人資料' },

            // 詳細欄位: 聯絡資訊
            { key: 'regionAddress', label: '所在地區', type: 'text', group: '聯絡資訊' },
            { key: 'homeAddress', label: '戶籍地址', type: 'text', group: '聯絡資訊' },
            { key: 'landlinePhone', label: '室內電話', type: 'text', group: '聯絡資訊' },
            { key: 'commAddressSameAsHome', label: '通訊地址是否同戶籍地', type: 'checkbox', group: '聯絡資訊' },
            { key: 'commAddress', label: '通訊地址', type: 'text', group: '聯絡資訊' },
            { key: 'commPhone', label: '通訊電話', type: 'text', group: '聯絡資訊' },
            { key: 'residenceDuration', label: '現居地住多久', type: 'text', group: '聯絡資訊' },
            { key: 'residenceOwner', label: '居住地持有人', type: 'text', group: '聯絡資訊' },
            { key: 'telecomOperator', label: '電信業者', type: 'text', group: '聯絡資訊' },

            // 公司資料
            { key: 'email', label: '電子郵件', type: 'email', group: '公司資料' },
            { key: 'companyName', label: '公司名稱', type: 'text', group: '公司資料' },
            { key: 'companyPhone', label: '公司電話', type: 'text', group: '公司資料' },
            { key: 'companyAddress', label: '公司地址', type: 'text', group: '公司資料' },
            { key: 'jobTitle', label: '職稱', type: 'text', group: '公司資料' },
            { key: 'monthlyIncome', label: '月收入', type: 'text', group: '公司資料' },
            { key: 'hasLaborInsurance', label: '有無新轉勞保', type: 'checkbox', group: '公司資料' },
            { key: 'companyTenure', label: '目前公司在職多久', type: 'text', group: '公司資料' },

            // 其他資訊
            { key: 'id', label: '案件編號', type: 'text', group: '其他資訊', readonly: true }, // ID is caseId
            { key: 'demandAmount', label: '需求金額', type: 'text', group: '其他資訊' },
            { key: 'customField', label: '自定義欄位', type: 'textarea', group: '其他資訊' },

            // 緊急聯絡人
            { key: 'emergencyContact1Name', label: '聯絡人①姓名', type: 'text', group: '緊急聯絡人' },
            { key: 'emergencyContact1Relationship', label: '聯絡人①關係', type: 'text', group: '緊急聯絡人' },
            { key: 'emergencyContact1Phone', label: '聯絡人①電話', type: 'text', group: '緊急聯絡人' },
            { key: 'contactTime1', label: '方便聯絡時間', type: 'text', group: '緊急聯絡人' },
            { key: 'confidential1', label: '是否保密', type: 'checkbox', group: '緊急聯絡人' },
            { key: 'emergencyContact2Name', label: '聯絡人②姓名', type: 'text', group: '緊急聯絡人' },
            { key: 'emergencyContact2Relationship', label: '聯絡人②關係', type: 'text', group: '緊急聯絡人' },
            { key: 'emergencyContact2Phone', label: '聯絡人②電話', type: 'text', group: '緊急聯絡人' },
            { key: 'contactTime2', label: '方便聯絡時間', type: 'text', group: '緊急聯絡人' },
            { key: 'confidential2', label: '是否保密', type: 'checkbox', group: '緊急聯絡人' },
            { key: 'referrer', label: '介紹人', type: 'text', group: '緊急聯絡人' }
        ];

        fieldsConfig.forEach(field => {
            const inputElement = document.getElementById(field.key);
            if (inputElement) {
                if (field.type === 'checkbox') {
                    caseToUpdate[field.key] = inputElement.checked;
                } else if (field.type === 'custom-line-info') {
                    const lineDisplayNameInput = document.getElementById('lineDisplayName' + caseToUpdate.id);
                    const lineAddFriendIdInput = document.getElementById('lineAddFriendId' + caseToUpdate.id);
                    caseToUpdate[field.key] = {
                        lineDisplayName: lineDisplayNameInput ? lineDisplayNameInput.value : '',
                        lineAddFriendId: lineAddFriendIdInput ? lineAddFriendIdInput.value : ''
                    };
                } else if (field.type === 'custom-contact-info') {
                    const contactEmailInput = document.getElementById('contactEmail' + caseToUpdate.id);
                    const contactPhoneInput = document.getElementById('contactPhone' + caseToUpdate.id);
                    caseToUpdate[field.key] = {
                        contactEmail: contactEmailInput ? contactEmailInput.value : '',
                        contactPhone: contactPhoneInput ? contactPhoneInput.value : ''
                    };
                } else {
                    caseToUpdate[field.key] = inputElement.value;
                }
            }
        });

        console.log('Case updated:', caseToUpdate); // Debug log

        // If status changed, re-render appropriate tables and update badge counts
        if (caseToUpdate.status !== oldStatus) {
            // Re-render the old table (if current case was filtered by old status)
            const oldSectionId = statusMappings[oldStatus]?.sectionId;
            if (oldSectionId) {
                renderTable(casesData, oldSectionId.replace('-content', '-table'));
            }

            // Re-render the new table (where the case should now appear)
            const newSectionId = statusMappings[caseToUpdate.status]?.sectionId;
            if (newSectionId) {
                renderTable(casesData, newSectionId.replace('-content', '-table'));
                showContentSection(newSectionId, statusMappings[caseToUpdate.status].displayName); // Switch to new section
            }
            updateBadgeCounts(); // Update all badge counts
        } else {
            // If status didn't change, re-render the current table to reflect other changes
            const currentSectionId = statusMappings[caseToUpdate.status]?.sectionId;
            if (currentSectionId) {
                renderTable(casesData, currentSectionId.replace('-content', '-table'));
            }
        }
    }

    // Add event listeners for the modal buttons
    // Removed modal close button as it's no longer a modal
    // const editModalCloseButton = document.querySelector('.edit-modal-close');
    // if (editModalCloseButton) {
    //     editModalCloseButton.addEventListener('click', closeEditCaseModal);
    // }
    // const cancelEditButton = document.getElementById('cancel-edit-button');
    // if (cancelEditButton) {
    //     cancelEditButton.addEventListener('click', closeEditCaseModal);
    // }
    // const saveCaseButton = document.getElementById('save-case-button');
    // if (saveCaseButton) {
    //     saveCaseButton.addEventListener('click', () => {
    //         if (currentEditingCaseId) {
    //             saveEditedCase(currentEditingCaseId);
    //         }
    //     });
    // }

    // Initial render for network-inbound-content
    showContentSection('network-inbound-content', '網路進線');
    renderTable(casesData, 'network-inbound-table');
    updateBadgeCounts(); // Initial update of badge counts
});
