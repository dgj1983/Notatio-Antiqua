/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#ifndef NASETTINGS_H
#define NASETTINGS_H

#include <QDialog>
#include <QSettings>
#include <QMessageBox>

namespace Ui {
    class NASettings;
}

class NASettings : public QDialog {
    Q_OBJECT
public:
    NASettings(QWidget *parent = 0);
    ~NASettings();

protected:
    void changeEvent(QEvent *e);

private:
    Ui::NASettings *ui;
private slots:
    void modifyIni();
};

#endif // NASETTINGS_H
