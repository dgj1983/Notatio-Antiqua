#include "naclefselect.h"
#include "ui_naclefselect.h"

NAClefSelect::NAClefSelect(QWidget *parent) :
    QDialog(parent),
    ui(new Ui::NAClefSelect)
{
    ui->setupUi(this);
}

NAClefSelect::~NAClefSelect()
{
    delete ui;
}

void NAClefSelect::on_buttonBox_accepted()
{
    if (ui->cclef->isChecked())
        clefS ="(c";
     else if (ui->fclef->isChecked())
         clefS = "(f";
     if (ui->bdurum->isChecked())
         clefS = clefS+"b";
     clefS = clefS + ui->pitch->text()+") ";
}
