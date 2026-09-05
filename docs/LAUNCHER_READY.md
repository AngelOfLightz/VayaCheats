# VayaCheats V3.1 - Launcher Ready Architecture

## Overview

The Launcher Ready Architecture prepares VayaCheats for a future Windows Launcher application. The launcher will communicate exclusively through APIs, never directly accessing the database, ensuring security and scalability.

---

## Launcher Capabilities

### Core Features

1. **Authentication**
   - Login with email/password
   - Login with Discord (future)
   - Remember me functionality
   - Session persistence
   - Auto-login on startup

2. **License Validation**
   - Real-time license validation
   - Hardware binding
   - Activation management
   - Expiration checking
   - Offline validation (cached)

3. **Product Download**
   - Product listing
   - Version checking
   - Secure download
   - Resume support
   - Progress tracking

4. **Product Updates**
   - Auto-update checking
   - Delta updates
   - Version history
   - Rollback support
   - Update scheduling

5. **News Feed**
   - Latest announcements
   - Product updates
   - Maintenance notices
   - Feature highlights

6. **Notifications**
   - License expiration warnings
   - Product updates
   - System announcements
   - Security alerts

7. **Version Check**
   - Launcher version checking
   - Auto-update launcher
   - Changelog display
   - Forced updates

8. **File Verification**
   - File integrity checking
   - Hash verification
   - Corruption detection
   - Repair functionality

9. **Auto Update**
   - Background updates
   - Silent updates
   - Update scheduling
   - Update rollback

10. **Repair Installation**
    - File corruption repair
    - Re-download corrupted files
    - Configuration repair
    - Cache clearing

---

## Launcher Architecture

### Communication Layer

```
Windows Launcher
├── UI Layer
│   ├── Login Screen
│   ├── Dashboard
│   ├── Product List
│   ├── Download Manager
│   └── Settings
├── API Client
│   ├── HTTP Client
│   ├── Authentication
│   ├── Request Builder
│   └── Response Parser
├── Cache Layer
│   ├── License Cache
│   ├── Product Cache
│   ├── File Cache
│   └── Offline Mode
└── File Manager
    ├── Download Manager
    ├── Update Manager
    ├── Verification Manager
    └── Repair Manager
```

### API Communication

All launcher communication goes through the VayaCheats API:

```
Launcher
    ↓
HTTP Request (JSON)
    ↓
API Gateway
    ↓
Authentication (JWT/API Key)
    ↓
Rate Limiting
    ↓
API Controller
    ↓
Business Logic
    ↓
Database
    ↓
Response (JSON)
    ↓
Launcher
```

---

## Launcher API Endpoints

### Authentication Endpoints

```
POST /api/v1/launcher/auth/login
Request: {
    "email": "user@example.com",
    "password": "password",
    "hardware_id": "abc123",
    "launcher_version": "1.0.0"
}
Response: {
    "success": true,
    "data": {
        "access_token": "jwt_token",
        "refresh_token": "refresh_token",
        "user": {...}
    }
}

POST /api/v1/launcher/auth/refresh
Request: {
    "refresh_token": "refresh_token"
}
Response: {
    "success": true,
    "data": {
        "access_token": "new_jwt_token"
    }
}

POST /api/v1/launcher/auth/logout
Request: {
    "access_token": "jwt_token"
}
Response: {
    "success": true
}
```

### License Endpoints

```
POST /api/v1/launcher/license/validate
Request: {
    "license_key": "VAYA-XXXX-XXXX-XXXX-XXXX",
    "hardware_id": "abc123",
    "launcher_version": "1.0.0"
}
Response: {
    "success": true,
    "data": {
        "valid": true,
        "license": {
            "plan": "Pro",
            "level": 2,
            "expires_at": "2026-12-31T23:59:59Z",
            "activations": {
                "current": 1,
                "maximum": 2
            }
        },
        "products": [
            {
                "id": 1,
                "name": "Advanced Cheat",
                "minimum_level": 2,
                "accessible": true,
                "current_version": "2.1.0",
                "latest_version": "2.1.0"
            }
        ]
    }
}

POST /api/v1/launcher/license/activate
Request: {
    "license_key": "VAYA-XXXX-XXXX-XXXX-XXXX",
    "hardware_id": "abc123"
}
Response: {
    "success": true,
    "data": {
        "activated": true,
        "activation_id": 123
    }
}
```

### Product Endpoints

```
GET /api/v1/launcher/products
Response: {
    "success": true,
    "data": {
        "products": [
            {
                "id": 1,
                "name": "Advanced Cheat",
                "description": "...",
                "minimum_level": 2,
                "current_version": "2.1.0",
                "latest_version": "2.1.0",
                "size_bytes": 52428800,
                "file_hash": "sha256:abc123...",
                "accessible": true
            }
        ]
    }
}

GET /api/v1/launcher/products/{id}/download
Response: {
    "success": true,
    "data": {
        "download_url": "https://cdn.vayacheats.com/products/1/file.zip",
        "file_hash": "sha256:abc123...",
        "file_size": 52428800,
        "version": "2.1.0",
        "expires_at": "2026-07-31T21:00:00Z"
    }
}

GET /api/v1/launcher/products/{id}/versions
Response: {
    "success": true,
    "data": {
        "versions": [
            {
                "version": "2.1.0",
                "released_at": "2026-07-15T00:00:00Z",
                "changelog": "Bug fixes and improvements"
            },
            {
                "version": "2.0.0",
                "released_at": "2026-06-01T00:00:00Z",
                "changelog": "Major update"
            }
        ]
    }
}
```

### News Endpoints

```
GET /api/v1/launcher/news
Response: {
    "success": true,
    "data": {
        "news": [
            {
                "id": 1,
                "title": "New Product Released",
                "content": "...",
                "type": "product",
                "created_at": "2026-07-30T00:00:00Z"
            }
        ]
    }
}
```

### Notification Endpoints

```
GET /api/v1/launcher/notifications
Response: {
    "success": true,
    "data": {
        "notifications": [
            {
                "id": 1,
                "type": "license_expiring",
                "title": "License Expiring Soon",
                "message": "Your license expires in 7 days",
                "created_at": "2026-07-24T00:00:00Z"
            }
        ]
    }
}

POST /api/v1/launcher/notifications/{id}/read
Response: {
    "success": true
}
```

### Version Endpoints

```
GET /api/v1/launcher/version
Response: {
    "success": true,
    "data": {
        "latest_version": "1.2.0",
        "current_version": "1.0.0",
        "update_required": true,
        "update_url": "https://cdn.vayacheats.com/launcher/v1.2.0/setup.exe",
        "changelog": "Bug fixes and improvements",
        "force_update": false
    }
}
```

### Report Endpoints

```
POST /api/v1/launcher/report
Request: {
    "type": "error",
    "message": "Download failed",
    "stack_trace": "...",
    "launcher_version": "1.0.0",
    "hardware_id": "abc123"
}
Response: {
    "success": true
}
```

---

## Launcher Security

### Hardware ID Generation

```csharp
public class HardwareIDGenerator
{
    public static string Generate()
    {
        var components = new List<string>();
        
        // CPU ID
        components.Add(GetCPUId());
        
        // Motherboard ID
        components.Add(GetMotherboardId());
        
        // MAC Address
        components.Add(GetMacAddress());
        
        // Disk Serial
        components.Add(GetDiskSerial());
        
        // Combine and hash
        var combined = string.Join("|", components);
        var hash = SHA256.HashData(Encoding.UTF8.GetBytes(combined));
        
        return BitConverter.ToString(hash).Replace("-", "").Substring(0, 16);
    }
    
    private static string GetCPUId()
    {
        // Get CPU identifier
        using var searcher = new ManagementObjectSearcher("SELECT ProcessorId FROM Win32_Processor");
        foreach (var obj in searcher.Get())
        {
            return obj["ProcessorId"].ToString();
        }
        return "unknown";
    }
    
    private static string GetMotherboardId()
    {
        // Get motherboard serial
        using var searcher = new ManagementObjectSearcher("SELECT SerialNumber FROM Win32_BaseBoard");
        foreach (var obj in searcher.Get())
        {
            return obj["SerialNumber"].ToString();
        }
        return "unknown";
    }
    
    private static string GetMacAddress()
    {
        // Get first MAC address
        foreach (var nic in NetworkInterface.GetAllNetworkInterfaces())
        {
            if (nic.OperationalStatus == OperationalStatus.Up && 
                nic.NetworkInterfaceType != NetworkInterfaceType.Loopback)
            {
                return nic.GetPhysicalAddress().ToString();
            }
        }
        return "unknown";
    }
    
    private static string GetDiskSerial()
    {
        // Get primary disk serial
        using var searcher = new ManagementObjectSearcher("SELECT SerialNumber FROM Win32_DiskDrive WHERE Index = 0");
        foreach (var obj in searcher.Get())
        {
            return obj["SerialNumber"].ToString();
        }
        return "unknown";
    }
}
```

### Secure Storage

```csharp
public class SecureStorage
{
    private static byte[] entropy = Encoding.UTF8.GetBytes("VayaCheatsLauncher");
    
    public static void Save(string key, string value)
    {
        var encrypted = ProtectedData.Protect(
            Encoding.UTF8.GetBytes(value),
            entropy,
            DataProtectionScope.CurrentUser
        );
        
        var path = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "VayaCheats",
            "secure.dat"
        );
        
        // Store encrypted data
    }
    
    public static string Load(string key)
    {
        // Load encrypted data
        var decrypted = ProtectedData.Unprotect(
            encryptedData,
            entropy,
            DataProtectionScope.CurrentUser
        );
        
        return Encoding.UTF8.GetString(decrypted);
    }
}
```

---

## Launcher Offline Mode

### Offline Validation

```csharp
public class OfflineValidator
{
    private string cachePath;
    
    public OfflineValidator()
    {
        cachePath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "VayaCheats",
            "cache"
        );
    }
    
    public bool ValidateLicense(string licenseKey, string hardwareId)
    {
        // Try online validation first
        if (TryOnlineValidation(licenseKey, hardwareId, out var result))
        {
            CacheValidationResult(result);
            return result.Valid;
        }
        
        // Fall back to cached validation
        return TryCachedValidation(licenseKey, hardwareId);
    }
    
    private bool TryOnlineValidation(string licenseKey, string hardwareId, out ValidationResult result)
    {
        try
        {
            var response = apiClient.Post("/launcher/license/validate", new
            {
                license_key = licenseKey,
                hardware_id = hardwareId
            });
            
            result = response.Data.ToObject<ValidationResult>();
            return true;
        }
        catch
        {
            result = null;
            return false;
        }
    }
    
    private bool TryCachedValidation(string licenseKey, string hardwareId)
    {
        var cacheFile = Path.Combine(cachePath, "license_cache.json");
        
        if (!File.Exists(cacheFile))
        {
            return false;
        }
        
        var cached = JsonConvert.DeserializeObject<LicenseCache>(File.ReadAllText(cacheFile));
        
        // Check if cache is recent (within 7 days)
        if ((DateTime.Now - cached.CachedAt).TotalDays > 7)
        {
            return false;
        }
        
        // Check if license matches
        if (cached.LicenseKey != licenseKey || cached.HardwareId != hardwareId)
        {
            return false;
        }
        
        // Check if not expired
        if (cached.ExpiresAt < DateTime.Now)
        {
            return false;
        }
        
        return cached.Valid;
    }
    
    private void CacheValidationResult(ValidationResult result)
    {
        var cache = new LicenseCache
        {
            LicenseKey = result.LicenseKey,
            HardwareId = result.HardwareId,
            Valid = result.Valid,
            ExpiresAt = result.ExpiresAt,
            CachedAt = DateTime.Now
        };
        
        var cacheFile = Path.Combine(cachePath, "license_cache.json");
        File.WriteAllText(cacheFile, JsonConvert.SerializeObject(cache));
    }
}
```

---

## Launcher Update System

### Update Manager

```csharp
public class UpdateManager
{
    private string updateUrl;
    private string currentVersion;
    
    public async Task<UpdateInfo> CheckForUpdatesAsync()
    {
        var response = await apiClient.GetAsync("/launcher/version");
        var data = response.Data.ToObject<VersionResponse>();
        
        return new UpdateInfo
        {
            CurrentVersion = currentVersion,
            LatestVersion = data.LatestVersion,
            UpdateRequired = data.UpdateRequired,
            UpdateUrl = data.UpdateUrl,
            Changelog = data.Changelog,
            ForceUpdate = data.ForceUpdate
        };
    }
    
    public async Task DownloadUpdateAsync(string url, IProgress<double> progress)
    {
        using var client = new HttpClient();
        var response = await client.GetAsync(url, HttpCompletionOption.ResponseHeadersRead);
        
        var totalBytes = response.Content.Headers.ContentLength ?? 0L;
        var receivedBytes = 0L;
        
        using var contentStream = await response.Content.ReadAsStreamAsync();
        using var fileStream = new FileStream("update.exe", FileMode.Create, FileAccess.Write, FileShare.None);
        
        var buffer = new byte[8192];
        int bytesRead;
        
        while ((bytesRead = await contentStream.ReadAsync(buffer, 0, buffer.Length)) > 0)
        {
            await fileStream.WriteAsync(buffer, 0, bytesRead);
            receivedBytes += bytesRead;
            
            progress.Report((double)receivedBytes / totalBytes * 100);
        }
    }
    
    public async Task InstallUpdateAsync()
    {
        // Create batch file to replace launcher
        var batchContent = $@"
            @echo off
            timeout /t 2 /nobreak >nul
            taskkill /f /im VayaCheatsLauncher.exe
            move update.exe VayaCheatsLauncher.exe
            start VayaCheatsLauncher.exe
            del update.bat
        ";
        
        File.WriteAllText("update.bat", batchContent);
        
        // Start batch file and exit
        Process.Start("update.bat");
        Environment.Exit(0);
    }
}
```

---

## Launcher Download Manager

### Download Manager

```csharp
public class DownloadManager
{
    private ConcurrentDictionary<int, DownloadTask> downloads = new();
    
    public async Task<int> DownloadProductAsync(int productId, string licenseKey, IProgress<DownloadProgress> progress)
    {
        // Get download URL from API
        var response = await apiClient.GetAsync($"/launcher/products/{productId}/download");
        var data = response.Data.ToObject<DownloadInfo>();
        
        var taskId = Interlocked.Increment(ref downloadIdCounter);
        
        var task = new DownloadTask
        {
            Id = taskId,
            ProductId = productId,
            Url = data.DownloadUrl,
            ExpectedHash = data.FileHash,
            ExpectedSize = data.FileSize,
            Version = data.Version
        };
        
        lock (downloads)
        {
            downloads[taskId] = task;
        }
        
        // Start download
        _ = DownloadAsync(task, progress);
        
        return taskId;
    }
    
    private async Task DownloadAsync(DownloadTask task, IProgress<DownloadProgress> progress)
    {
        using var client = new HttpClient();
        var response = await client.GetAsync(task.Url, HttpCompletionOption.ResponseHeadersRead);
        
        var totalBytes = response.Content.Headers.ContentLength ?? 0L;
        var receivedBytes = 0L;
        
        var tempPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "VayaCheats",
            "downloads",
            $"temp_{task.Id}.zip"
        );
        
        Directory.CreateDirectory(Path.GetDirectoryName(tempPath));
        
        using var contentStream = await response.Content.ReadAsStreamAsync();
        using var fileStream = new FileStream(tempPath, FileMode.Create, FileAccess.Write, FileShare.None);
        
        var buffer = new byte[8192];
        int bytesRead;
        
        while ((bytesRead = await contentStream.ReadAsync(buffer, 0, buffer.Length)) > 0)
        {
            await fileStream.WriteAsync(buffer, 0, bytesRead);
            receivedBytes += bytesRead;
            
            task.DownloadedBytes = receivedBytes;
            task.Progress = (double)receivedBytes / totalBytes * 100;
            
            progress?.Report(new DownloadProgress
            {
                TaskId = task.Id,
                DownloadedBytes = receivedBytes,
                TotalBytes = totalBytes,
                Progress = task.Progress,
                Speed = CalculateSpeed(receivedBytes, task.StartTime)
            });
        }
        
        // Verify file
        if (!await VerifyFileAsync(tempPath, task.ExpectedHash))
        {
            File.Delete(tempPath);
            throw new FileVerificationException("File hash mismatch");
        }
        
        task.Status = DownloadStatus.Completed;
        task.FilePath = tempPath;
    }
    
    private async Task<bool> VerifyFileAsync(string filePath, string expectedHash)
    {
        using var sha256 = SHA256.Create();
        using var stream = File.OpenRead(filePath);
        
        var hash = await sha256.ComputeHashAsync(stream);
        var hashString = BitConverter.ToString(hash).Replace("-", "").ToLower();
        
        return hashString == expectedHash.Replace("sha256:", "");
    }
    
    private double CalculateSpeed(long bytes, DateTime startTime)
    {
        var elapsed = (DateTime.Now - startTime).TotalSeconds;
        return bytes / elapsed;
    }
}
```

---

## Launcher File Verification

### File Verification

```csharp
public class FileVerifier
{
    public async Task<VerificationResult> VerifyProductAsync(string productPath, string expectedHash)
    {
        if (!File.Exists(productPath))
        {
            return new VerificationResult
            {
                Valid = false,
                Error = "File not found"
            };
        }
        
        var actualHash = await CalculateHashAsync(productPath);
        
        if (actualHash != expectedHash.Replace("sha256:", ""))
        {
            return new VerificationResult
            {
                Valid = false,
                Error = "Hash mismatch",
                ExpectedHash = expectedHash,
                ActualHash = actualHash
            };
        }
        
        return new VerificationResult
        {
            Valid = true,
            Hash = actualHash
        };
    }
    
    private async Task<string> CalculateHashAsync(string filePath)
    {
        using var sha256 = SHA256.Create();
        using var stream = File.OpenRead(filePath);
        
        var hash = await sha256.ComputeHashAsync(stream);
        return BitConverter.ToString(hash).Replace("-", "").ToLower();
    }
    
    public async Task<RepairResult> RepairProductAsync(string productPath, int productId, string licenseKey)
    {
        // Delete corrupted file
        File.Delete(productPath);
        
        // Re-download
        var downloadManager = new DownloadManager();
        var taskId = await downloadManager.DownloadProductAsync(productId, licenseKey, null);
        
        // Wait for download
        var task = downloadManager.GetTask(taskId);
        while (task.Status != DownloadStatus.Completed)
        {
            await Task.Delay(1000);
        }
        
        // Verify new file
        var verification = await VerifyProductAsync(task.FilePath, task.ExpectedHash);
        
        return new RepairResult
        {
            Success = verification.Valid,
            NewPath = task.FilePath
        };
    }
}
```

---

## Launcher API Client

### API Client Implementation

```csharp
public class LauncherApiClient
{
    private string baseUrl;
    private string accessToken;
    private string refreshToken;
    private HttpClient httpClient;
    
    public LauncherApiClient(string baseUrl)
    {
        this.baseUrl = baseUrl;
        this.httpClient = new HttpClient();
        this.httpClient.BaseAddress = new Uri(baseUrl);
        this.httpClient.DefaultRequestHeaders.Add("User-Agent", $"VayaCheatsLauncher/{GetVersion()}");
    }
    
    public async Task<APIResponse> LoginAsync(string email, string password, string hardwareId)
    {
        var response = await PostAsync("/launcher/auth/login", new
        {
            email = email,
            password = password,
            hardware_id = hardwareId,
            launcher_version = GetVersion()
        });
        
        if (response.Success)
        {
            var data = response.Data.ToObject<LoginResponse>();
            this.accessToken = data.AccessToken;
            this.refreshToken = data.RefreshToken;
            
            SetAuthHeader();
        }
        
        return response;
    }
    
    public async Task<APIResponse> GetAsync(string endpoint)
    {
        EnsureAuthenticated();
        
        var response = await httpClient.GetAsync(endpoint);
        return await ParseResponseAsync(response);
    }
    
    public async Task<APIResponse> PostAsync(string endpoint, object data)
    {
        EnsureAuthenticated();
        
        var json = JsonConvert.SerializeObject(data);
        var content = new StringContent(json, Encoding.UTF8, "application/json");
        
        var response = await httpClient.PostAsync(endpoint, content);
        return await ParseResponseAsync(response);
    }
    
    private void EnsureAuthenticated()
    {
        if (string.IsNullOrEmpty(accessToken))
        {
            throw new AuthenticationException("Not authenticated");
        }
        
        SetAuthHeader();
    }
    
    private void SetAuthHeader()
    {
        httpClient.DefaultRequestHeaders.Authorization = 
            new AuthenticationHeaderValue("Bearer", accessToken);
    }
    
    private async Task<APIResponse> ParseResponseAsync(HttpResponseMessage response)
    {
        var content = await response.Content.ReadAsStringAsync();
        var apiResponse = JsonConvert.DeserializeObject<APIResponse>(content);
        
        if (!response.IsSuccessStatusCode)
        {
            if (response.StatusCode == HttpStatusCode.Unauthorized)
            {
                // Try refresh token
                await RefreshTokenAsync();
            }
        }
        
        return apiResponse;
    }
    
    private async Task RefreshTokenAsync()
    {
        var response = await PostAsync("/launcher/auth/refresh", new
        {
            refresh_token = refreshToken
        });
        
        if (response.Success)
        {
            var data = response.Data.ToObject<RefreshResponse>();
            this.accessToken = data.AccessToken;
            SetAuthHeader();
        }
        else
        {
            throw new AuthenticationException("Token refresh failed");
        }
    }
    
    private string GetVersion()
    {
        return Assembly.GetExecutingAssembly().GetName().Version.ToString();
    }
}
```

---

## Launcher Configuration

### Configuration Storage

```csharp
public class LauncherConfig
{
    private string configPath;
    private JObject config;
    
    public LauncherConfig()
    {
        configPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "VayaCheats",
            "config.json"
        );
        
        Load();
    }
    
    public string ApiUrl
    {
        get => config["api_url"]?.ToString();
        set => config["api_url"] = value;
    }
    
    public string LicenseKey
    {
        get => config["license_key"]?.ToString();
        set => config["license_key"] = value;
    }
    
    public bool AutoUpdate
    {
        get => config["auto_update"]?.ToObject<bool>() ?? true;
        set => config["auto_update"] = value;
    }
    
    public bool StartMinimized
    {
        get => config["start_minimized"]?.ToObject<bool>() ?? false;
        set => config["start_minimized"] = value;
    }
    
    public void Load()
    {
        if (File.Exists(configPath))
        {
            var json = File.ReadAllText(configPath);
            config = JObject.Parse(json);
        }
        else
        {
            config = new JObject();
            SetDefaults();
        }
    }
    
    public void Save()
    {
        Directory.CreateDirectory(Path.GetDirectoryName(configPath));
        File.WriteAllText(configPath, config.ToString());
    }
    
    private void SetDefaults()
    {
        ApiUrl = "https://api.vayacheats.com";
        AutoUpdate = true;
        StartMinimized = false;
    }
}
```

---

## Launcher Database Schema

### Launcher Sessions Table

```sql
CREATE TABLE launcher_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hardware_id VARCHAR(64) NOT NULL,
    launcher_version VARCHAR(20),
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_hardware_id (hardware_id),
    INDEX idx_last_seen_at (last_seen_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Launcher Downloads Table

```sql
CREATE TABLE launcher_downloads (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    license_id INT NULL,
    hardware_id VARCHAR(64) NOT NULL,
    version VARCHAR(20),
    file_size BIGINT,
    file_hash VARCHAR(64),
    status ENUM('started', 'completed', 'failed', 'cancelled') DEFAULT 'started',
    download_time INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_hardware_id (hardware_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Launcher Reports Table

```sql
CREATE TABLE launcher_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    hardware_id VARCHAR(64),
    launcher_version VARCHAR(20),
    report_type ENUM('error', 'crash', 'performance', 'feature_request') NOT NULL,
    title VARCHAR(255),
    message TEXT,
    stack_trace TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_hardware_id (hardware_id),
    INDEX idx_report_type (report_type),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Launcher Security Best Practices

### Security Measures

1. **No Direct Database Access**: Launcher never connects to database
2. **API-Only Communication**: All data through authenticated APIs
3. **Hardware Binding**: Licenses bound to hardware IDs
4. **Secure Storage**: Sensitive data encrypted using Windows DPAPI
5. **Certificate Pinning**: Prevent MITM attacks
6. **Code Signing**: Launcher executable signed for authenticity
7. **Anti-Tampering**: Integrity checks on launcher files
8. **Rate Limiting**: API calls rate-limited per launcher
9. **Session Management**: Secure JWT token handling
10. **Offline Mode**: Cached validation with time limits

### Certificate Pinning

```csharp
public class CertificatePinning
{
    private static readonly string[] PinnedCertificates = new[]
    {
        "sha256/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=",
        // Add actual certificate hashes
    };
    
    public static void ValidateCertificate(HttpRequestMessage request)
    {
        var handler = new HttpClientHandler();
        handler.ServerCertificateCustomValidationCallback = (message, cert, chain, errors) =>
        {
            if (cert == null) return false;
            
            var certHash = Convert.ToBase64String(cert.GetCertHash());
            
            return PinnedCertificates.Contains(certHash);
        };
    }
}
```

---

## Summary

The Launcher Ready Architecture provides:
- **API-only communication** - No direct database access
- **Secure authentication** - JWT tokens with refresh
- **Hardware binding** - License activation per device
- **Offline mode** - Cached validation with time limits
- **Auto-update system** - Background updates with rollback
- **Download management** - Resume support, progress tracking
- **File verification** - Hash verification, corruption repair
- **Secure storage** - Windows DPAPI for sensitive data
- **Certificate pinning** - MITM attack prevention
- **Comprehensive logging** - All launcher actions tracked

Launcher capabilities:
- Login, License Validation, Product Download
- Product Updates, News Feed, Notifications
- Version Check, File Verification, Auto Update, Repair Installation
