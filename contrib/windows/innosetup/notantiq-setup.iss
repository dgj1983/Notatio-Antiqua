; Script generated by the Inno Setup Script Wizard.
; SEE THE DOCUMENTATION FOR DETAILS ON CREATING INNO SETUP SCRIPT FILES!

#define MyAppName "Notatio Antiqua"
#define MyAppVersion "0.8b"
#define MyAppPublisher "DGSOFTWARE"
#define MyAppURL "http://www.dgippner.de/blog2.php"
#define MyAppExeName "NotAntiq.exe"

[Setup]
; NOTE: The value of AppId uniquely identifies this application.
; Do not use the same AppId value in installers for other applications.
; (To generate a new GUID, click Tools | Generate GUID inside the IDE.)
AppID={{23CA5719-8D98-47B5-AA57-E0E2D975F6A0}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
;AppVerName={#MyAppName} {#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName={pf}\{#MyAppName}
DefaultGroupName={#MyAppName}
LicenseFile=C:\Documents and Settings\Administrator\My Documents\notantiq\gpl.txt
InfoBeforeFile=C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\innosetup\welcome.txt
InfoAfterFile=C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\innosetup\finished.txt
OutputDir=C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\innosetup\setup
OutputBaseFilename=notantiq-setup-0.7b
SetupIconFile=C:\Documents and Settings\Administrator\My Documents\notantiq\notantiq.ico
Compression=lzma/Max
SolidCompression=false

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"
Name: "french"; MessagesFile: "compiler:Languages\French.isl"
Name: "german"; MessagesFile: "compiler:Languages\German.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: "quicklaunchicon"; Description: "{cm:CreateQuickLaunchIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked; OnlyBelowVersion: 0,6.1

[Files]
Source: "C:\Documents and Settings\Administrator\My Documents\NotAntiq-build-desktop\release\NotAntiq.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "C:\Documents and Settings\Administrator\My Documents\notantiq\NaProg-de.qm"; DestDir: "{app}"; Flags: ignoreversion
Source: "C:\Documents and Settings\Administrator\My Documents\notantiq\NaProg-en.qm"; DestDir: "{app}"; Flags: ignoreversion
Source: "C:\Documents and Settings\Administrator\My Documents\notantiq\NaProg-fr.qm"; DestDir: "{app}"; Flags: ignoreversion
Source: "C:\Documents and Settings\Administrator\My Documents\notantiq\help\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\templates\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

; NOTE: Don't use "Flags: ignoreversion" on any shared system files
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\dlls\mingwm10.dll; DestDir: {sys}; Flags: promptifolder onlyifdoesntexist confirmoverwrite; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\dlls\libgcc_s_dw2-1.dll; DestDir: {sys}; Flags: confirmoverwrite promptifolder onlyifdoesntexist; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\dlls\QtCore4.dll; DestDir: {sys}; Flags: confirmoverwrite promptifolder onlyifdoesntexist; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\dlls\QtGui4.dll; DestDir: {sys}; Flags: confirmoverwrite promptifolder onlyifdoesntexist; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\notantiq.ico; DestDir: {app}; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\font\Ubuntu-B.ttf; DestDir: {fonts}; Flags: promptifolder onlyifdoesntexist; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\font\Ubuntu-BI.ttf; DestDir: {fonts}; Flags: promptifolder onlyifdoesntexist; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\font\Ubuntu-I.ttf; DestDir: {fonts}; Flags: promptifolder onlyifdoesntexist; 
Source: C:\Documents and Settings\Administrator\My Documents\notantiq\contrib\windows\font\Ubuntu-R.ttf; DestDir: {fonts}; Flags: promptifolder onlyifdoesntexist; 

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{group}\{cm:ProgramOnTheWeb,{#MyAppName}}"; Filename: "{#MyAppURL}"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; Filename: "{uninstallexe}"
Name: {commondesktop}\{#MyAppName}; Filename: {app}\{#MyAppExeName}; Tasks: desktopicon; IconFilename: {app}\NotAntiq.ico; 
Name: "{userappdata}\Microsoft\Internet Explorer\Quick Launch\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: quicklaunchicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, "&", "&&")}}"; Flags: nowait postinstall skipifsilent
