Set objShell = CreateObject("Shell.Application")
Set objWMI = GetObject("winmgmts:\\.\root\cimv2")
Set colProcesses = objWMI.ExecQuery("Select * from Win32_Process Where Name = 'cmd.exe'")

For Each objProcess in colProcesses
    objShell.MinimizeAll
Next
