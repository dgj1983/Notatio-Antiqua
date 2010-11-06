#include <QtDebug>
#include "naclefselect.h"
#include "ui_naclefselect.h"

NAClefSelect::NAClefSelect(QWidget *parent) :
    QDialog(parent),
    ui(new Ui::NAClefSelect)
{
    ui->setupUi(this);
    connect(ui->buttonBox,SIGNAL(accepted()),this,SLOT(on_NAClefSelect_accepted()));
}

NAClefSelect::~NAClefSelect()
{
    delete ui;
}

void NAClefSelect::on_NAClefSelect_accepted()
{
   if (ui->cclef->isChecked())
       clefS ="(c";
    else if (ui->fclef->isChecked())
        clefS = "(f";
    if (ui->bdurum->isChecked())
        clefS = clefS+"b";
    clefS = clefS + ui->pitch->text()+") ";
}
