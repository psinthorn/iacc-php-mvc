/**
 * Smart Dropdown Component
 * A reusable, searchable, sortable dropdown replacement
 * 
 * Usage:
 *   // Auto-initialize all dropdowns with class 'smart-dropdown'
 *   SmartDropdown.initAll();
 *   
 *   // Or initialize specific element
 *   new SmartDropdown(document.getElementById('mySelect'), {
 *       searchable: true,
 *       sortable: true,
 *       placeholder: 'Select an option...'
 *   });
 * 
 * @version 1.0.0
 * @date 2026-01-06
 */

class SmartDropdown {
    constructor(selectElement, options = {}) {
        if (!selectElement || selectElement.tagName !== 'SELECT') {
            console.error('SmartDropdown: Invalid select element');
            return;
        }
        
        this.select = selectElement;
        this.options = {
            searchable: options.searchable !== false,
            sortable: options.sortable !== false,
            placeholder: options.placeholder || 'Select...',
            searchPlaceholder: options.searchPlaceholder || '🔍 Search...',
            noResultsText: options.noResultsText || 'No results found',
            maxHeight: options.maxHeight || '300px',
            sortOrder: options.sortOrder || 'asc', // 'asc', 'desc', or 'none'
            ...options
        };
        
        this.isOpen = false;
        this.items = [];
        this.filteredItems = [];
        this.selectedIndex = -1;
        
        this.init();
    }
    
    init() {
        // Store reference on the element for later access
        this.select._smartDropdown = this;
        
        // Extract options from select
        this.extractOptions();
        
        // Hide original select
        this.select.style.display = 'none';
        
        // Create custom dropdown
        this.createDropdown();
        
        // Bind events
        this.bindEvents();
        
        // Set initial value
        this.setValue(this.select.value, false);
    }
    
    extractOptions() {
        this.items = [];
        const options = this.select.querySelectorAll('option');
        options.forEach((opt, index) => {
            this.items.push({
                value: opt.value,
                text: opt.textContent,
                index: index,
                disabled: opt.disabled,
                selected: opt.selected
            });
        });
        this.filteredItems = [...this.items];
    }
    
    createDropdown() {
        // Container
        this.container = document.createElement('div');
        this.container.className = 'sd-container';
        
        // Selected display
        this.display = document.createElement('div');
        this.display.className = 'sd-display';
        this.display.innerHTML = `
            <span class="sd-display-text">${this.options.placeholder}</span>
            <span class="sd-arrow">▼</span>
        `;
        
        // Dropdown panel
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'sd-dropdown';
        
        // Search and sort header
        let headerHTML = '<div class="sd-header">';
        
        if (this.options.searchable) {
            headerHTML += `<input type="text" class="sd-search" placeholder="${this.options.searchPlaceholder}">`;
        }
        
        if (this.options.sortable) {
            headerHTML += `
                <div class="sd-sort-buttons">
                    <button type="button" class="sd-sort-btn" data-sort="asc" title="Sort A-Z">↑ A-Z</button>
                    <button type="button" class="sd-sort-btn" data-sort="desc" title="Sort Z-A">↓ Z-A</button>
                </div>
            `;
        }
        
        headerHTML += '</div>';
        
        // Options list
        this.dropdown.innerHTML = headerHTML + '<div class="sd-options"></div>';
        
        // Assemble
        this.container.appendChild(this.display);
        this.container.appendChild(this.dropdown);
        
        // Insert after original select
        this.select.parentNode.insertBefore(this.container, this.select.nextSibling);
        
        // Get references
        this.searchInput = this.dropdown.querySelector('.sd-search');
        this.optionsList = this.dropdown.querySelector('.sd-options');
        this.sortButtons = this.dropdown.querySelectorAll('.sd-sort-btn');
        
        // Render options
        this.renderOptions();
        
        // Apply initial sort
        if (this.options.sortOrder !== 'none') {
            this.sortItems(this.options.sortOrder);
        }
    }
    
    renderOptions() {
        if (this.filteredItems.length === 0) {
            this.optionsList.innerHTML = `<div class="sd-no-results">${this.options.noResultsText}</div>`;
            return;
        }
        
        let html = '';
        this.filteredItems.forEach((item, idx) => {
            const selectedClass = item.value === this.select.value ? 'sd-option-selected' : '';
            const disabledClass = item.disabled ? 'sd-option-disabled' : '';
            html += `
                <div class="sd-option ${selectedClass} ${disabledClass}" 
                     data-value="${this.escapeHtml(item.value)}" 
                     data-index="${idx}">
                    ${this.escapeHtml(item.text)}
                </div>
            `;
        });
        this.optionsList.innerHTML = html;
    }
    
    bindEvents() {
        // Toggle dropdown
        this.display.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });
        
        // Search
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterItems(e.target.value);
            });
            
            this.searchInput.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
        
        // Sort buttons
        this.sortButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const order = btn.dataset.sort;
                this.sortItems(order);
                
                // Update active state
                this.sortButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
        
        // Option selection
        this.optionsList.addEventListener('click', (e) => {
            const option = e.target.closest('.sd-option');
            if (option && !option.classList.contains('sd-option-disabled')) {
                this.setValue(option.dataset.value);
                this.close();
            }
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.close();
            }
        });
        
        // Keyboard navigation
        this.container.addEventListener('keydown', (e) => {
            this.handleKeyboard(e);
        });
        
        // Sync with original select changes
        this.select.addEventListener('change', () => {
            this.setValue(this.select.value, false);
        });
    }
    
    handleKeyboard(e) {
        if (!this.isOpen) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                this.open();
            }
            return;
        }
        
        switch (e.key) {
            case 'Escape':
                this.close();
                break;
            case 'ArrowDown':
                e.preventDefault();
                this.navigateOptions(1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.navigateOptions(-1);
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0) {
                    const item = this.filteredItems[this.selectedIndex];
                    if (item && !item.disabled) {
                        this.setValue(item.value);
                        this.close();
                    }
                }
                break;
        }
    }
    
    navigateOptions(direction) {
        const options = this.optionsList.querySelectorAll('.sd-option:not(.sd-option-disabled)');
        if (options.length === 0) return;
        
        // Remove current highlight
        options.forEach(o => o.classList.remove('sd-option-highlighted'));
        
        // Calculate new index
        this.selectedIndex += direction;
        if (this.selectedIndex < 0) this.selectedIndex = options.length - 1;
        if (this.selectedIndex >= options.length) this.selectedIndex = 0;
        
        // Highlight and scroll into view
        const highlighted = options[this.selectedIndex];
        if (highlighted) {
            highlighted.classList.add('sd-option-highlighted');
            highlighted.scrollIntoView({ block: 'nearest' });
        }
    }
    
    filterItems(query) {
        query = query.toLowerCase().trim();
        
        if (!query) {
            this.filteredItems = [...this.items];
        } else {
            this.filteredItems = this.items.filter(item => 
                item.text.toLowerCase().includes(query) ||
                item.value.toLowerCase().includes(query)
            );
        }
        
        this.renderOptions();
    }
    
    sortItems(order) {
        this.options.sortOrder = order;
        
        this.filteredItems.sort((a, b) => {
            const textA = a.text.toLowerCase();
            const textB = b.text.toLowerCase();
            
            if (order === 'asc') {
                return textA.localeCompare(textB);
            } else {
                return textB.localeCompare(textA);
            }
        });
        
        this.renderOptions();
    }
    
    setValue(value, triggerChange = true) {
        const item = this.items.find(i => i.value === value);
        
        if (item) {
            this.display.querySelector('.sd-display-text').textContent = item.text;
            this.display.classList.add('sd-has-value');
            
            // Update original select
            if (this.select.value !== value) {
                this.select.value = value;
                if (triggerChange) {
                    this.select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            
            // Update visual selection
            this.renderOptions();
        } else {
            this.display.querySelector('.sd-display-text').textContent = this.options.placeholder;
            this.display.classList.remove('sd-has-value');
        }
    }
    
    getValue() {
        return this.select.value;
    }
    
    open() {
        this.isOpen = true;
        this.container.classList.add('sd-open');
        this.dropdown.style.maxHeight = this.options.maxHeight;
        
        if (this.searchInput) {
            this.searchInput.value = '';
            this.filterItems('');
            setTimeout(() => this.searchInput.focus(), 50);
        }
        
        this.selectedIndex = -1;
    }
    
    close() {
        this.isOpen = false;
        this.container.classList.remove('sd-open');
        this.selectedIndex = -1;
        
        // Remove highlights
        this.optionsList.querySelectorAll('.sd-option-highlighted')
            .forEach(o => o.classList.remove('sd-option-highlighted'));
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    refresh() {
        this.extractOptions();
        this.filteredItems = [...this.items];
        if (this.options.sortOrder !== 'none') {
            this.sortItems(this.options.sortOrder);
        } else {
            this.renderOptions();
        }
        this.setValue(this.select.value, false);
    }
    
    destroy() {
        this.container.remove();
        this.select.style.display = '';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Static method to initialize all dropdowns with class
    static initAll(selector = '.smart-dropdown', options = {}) {
        const elements = document.querySelectorAll(selector);
        const instances = [];
        elements.forEach(el => {
            // Get options from data attributes
            const dataOptions = {
                searchable: el.dataset.searchable !== 'false',
                sortable: el.dataset.sortable !== 'false',
                placeholder: el.dataset.placeholder,
                sortOrder: el.dataset.sortOrder || 'asc'
            };
            instances.push(new SmartDropdown(el, { ...options, ...dataOptions }));
        });
        return instances;
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SmartDropdown;
}
