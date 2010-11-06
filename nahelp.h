/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#ifndef NAHELP_H
#define NAHELP_H

#include <QDialog>
#include "ui_nahelp.h"

namespace Ui {
    class NAHelp;
}

class NAHelp : public QDialog
{
    Q_OBJECT

public:
    explicit NAHelp(QWidget *parent = 0);
    ~NAHelp();

private:
    Ui::NAHelp *ui;
};

#endif // NAHELP_H
