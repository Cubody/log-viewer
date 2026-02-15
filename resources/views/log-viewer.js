var _escDiv = document.createElement('div');

function _escHtml(t) {
    _escDiv.textContent = t;
    return _escDiv.innerHTML;
}

function _escRegex(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function logViewer() {
    return {
        allLines: [],
        search: '',
        levels: ['error', 'warn', 'info', 'debug'],
        rowH: 20,
        visibleCount: 50,
        scrollTop: 0,
        lastUserScroll: 0,
        filtered: [],
        loading: true,
        _timer: null,
        _polling: false,
        _currentFile: null,

        get totalHeight() { return this.filtered.length * this.rowH },
        get startIndex() { return Math.max(0, Math.floor(this.scrollTop / this.rowH) - 10) },
        get endIndex() { return Math.min(this.filtered.length, Math.floor(this.scrollTop / this.rowH) + this.visibleCount + 10) },
        get visibleLines() { return this.filtered.slice(this.startIndex, this.endIndex) },
        get offsetY() { return this.startIndex * this.rowH },

        recalcVisible: function () {
            var self = this;
            self.$nextTick(function () {
                if (self.$refs.scroller) {
                    self.visibleCount = Math.ceil(self.$refs.scroller.clientHeight / self.rowH) + 5;
                }
            });
        },

        applyFilters: function () {
            var s = this.search.toLowerCase();
            var lvls = this.levels;
            var out = [];
            for (var i = 0; i < this.allLines.length; i++) {
                var l = this.allLines[i];
                if (lvls.indexOf(l.level) < 0) continue;
                if (s && l.text.toLowerCase().indexOf(s) < 0) continue;
                out.push(l);
            }
            this.filtered = out;
        },

        appendFiltered: function (newLines) {
            var s = this.search.toLowerCase();
            var lvls = this.levels;
            for (var i = 0; i < newLines.length; i++) {
                var l = newLines[i];
                if (lvls.indexOf(l.level) < 0) continue;
                if (s && l.text.toLowerCase().indexOf(s) < 0) continue;
                this.filtered.push(l);
            }
        },

        toggleLevel: function (level) {
            var i = this.levels.indexOf(level);
            if (i >= 0) this.levels.splice(i, 1); else this.levels.push(level);
            this.applyFilters();
            this.scrollTop = 0;
            if (this.$refs.scroller) this.$refs.scroller.scrollTop = 0;
        },

        isActive: function (level) { return this.levels.indexOf(level) >= 0 },

        onScroll: function (e) {
            this.scrollTop = e.target.scrollTop;
            this.lastUserScroll = Date.now();
        },

        loadFile: function () {
            var self = this;
            self.allLines = [];
            self.filtered = [];
            self.scrollTop = 0;
            self.search = '';
            self.loading = true;
            if (self.$refs.scroller) self.$refs.scroller.scrollTop = 0;

            self.$wire.pollNewLines(0).then(function (result) {
                self.loading = false;
                if (!result || result.length === 0) return;
                if (result.reset) {
                    self.allLines = result.lines;
                } else {
                    self.allLines = result;
                }
                self.applyFilters();
                self.recalcVisible();
            }).catch(function () {
                self.loading = false;
            });
        },

        init: function () {
            var self = this;

            self.$watch('search', function () {
                self.applyFilters();
                self.scrollTop = 0;
                if (self.$refs.scroller) self.$refs.scroller.scrollTop = 0;
            });

            // Watch for file changes
            self._currentFile = self.$wire.get('selectedFile');
            self.$wire.$watch('selectedFile', function (val) {
                if (val !== self._currentFile) {
                    self._currentFile = val;
                    if (val) {
                        self.loadFile();
                    } else {
                        self.allLines = [];
                        self.filtered = [];
                        self.loading = false;
                    }
                }
            });

            // Initial load
            self.loadFile();

            // Incremental polling
            self._timer = setInterval(function () {
                if (self._polling || self.loading) return;
                self._polling = true;
                self.$wire.pollNewLines(self.allLines.length).then(function (result) {
                    self._polling = false;
                    if (!result || result.length === 0) return;
                    if (result.reset) {
                        self.allLines = result.lines;
                        self.applyFilters();
                        return;
                    }
                    Array.prototype.push.apply(self.allLines, result);
                    self.appendFiltered(result);
                }).catch(function () {
                    self._polling = false;
                });
            }, 5000);
        },

        destroy: function () {
            if (this._timer) clearInterval(this._timer);
        },

        levelColor: function (level) {
            var c = { error: '#ff7b72', warn: '#d29922', info: '#c9d1d9', debug: '#484f58', 'default': '#8b949e' };
            return c[level] || c['default'];
        },

        levelBg: function (level) {
            if (level === 'error') return 'rgba(239,68,68,0.08)';
            if (level === 'warn') return 'rgba(234,179,8,0.05)';
            return 'transparent';
        },

        highlight: function (text) {
            var t = _escHtml(text);
            if (!this.search) return t;
            try {
                var r = new RegExp('(' + _escRegex(this.search) + ')', 'gi');
                return t.replace(r, '<mark style="background:#bb800926;color:#d29922;border-radius:3px;padding:0 2px;box-shadow:0 0 0 1px #bb800966">$1</mark>');
            } catch (e) { return t }
        },

        btnStyle: function (level, rgb, txtColor) {
            return this.isActive(level)
                ? 'background:rgba(' + rgb + ',0.15);color:' + txtColor + ';border:1px solid rgba(' + rgb + ',0.35)'
                : 'background:rgba(255,255,255,0.05);color:#6b7280;border:1px solid rgba(255,255,255,0.05);opacity:0.5';
        }
    }
}
